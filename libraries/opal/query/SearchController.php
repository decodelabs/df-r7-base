<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\opal\query;

use DecodeLabs\Exceptional;
use DecodeLabs\Glitch\Dumpable;

use df\flex;
use df\opal;

class SearchController implements ISearchController, Dumpable
{
    use opal\query\TField;
    public const MAX_THRESHOLD_RATIO = 0.95;

    protected $_phrase = null;
    protected $_type = 'string';
    protected $_terms = null;
    protected $_fields = null;
    protected $_alias = 'relevance';
    protected $_isPrepared = false;
    protected $_query;

    public function __construct(IReadQuery $query, string $phrase = null, array $fields = null)
    {
        $this->_query = $query;

        if ($phrase !== null) {
            $this->setPhrase($phrase);
        }

        if ($fields !== null) {
            $this->setFields($fields);
        }
    }

    public function setPhrase($phrase)
    {
        $this->_phrase = (string)$phrase;

        if (is_numeric(ltrim($this->_phrase, '#'))) {
            $this->_phrase = ltrim($this->_phrase, '#');
            $this->_type = 'integer';
            $this->_terms = [];
        } elseif (flex\Guid::isValidString($this->_phrase)) {
            $this->_type = 'guid';
            $this->_terms = [];
        } else {
            $this->_terms = $this->_extractTerms($this->_phrase);
        }

        $this->_query->getSource()->addOutputField($this);

        return $this;
    }

    public function getPhrase()
    {
        return $this->_phrase;
    }

    protected function _extractTerms($phrase)
    {
        $parser = new flex\TermParser();
        return $parser->parse($phrase, true);
    }

    public function getTerms()
    {
        return $this->_terms;
    }

    public function setFields(array $fields)
    {
        $this->_fields = [];
        return $this->addFields($fields);
    }

    public function addFields(array $fields)
    {
        $source = $this->_query->getSource();
        $sourceManager = $this->_query->getSourceManager();

        foreach ($fields as $field => $set) {
            if (is_int($field) && is_string($set)) {
                $field = $set;
                $set = 1;
            }

            $field = $sourceManager->extrapolateIntrinsicField($source, $field);

            if (!$field) {
                continue;
            }

            $key = $field->getQualifiedName();

            if (!is_array($set)) {
                $set = ['weight' => (int)$set];
            }

            if ($schemaField = $field->getSource()->getFieldProcessor($field)) {
                $type = $schemaField->getSearchFieldType();
            } else {
                $type = 'string';
            }

            switch ($type) {
                case 'string':
                    if (!isset($set['operator'])) {
                        $set['operator'] = 'matches';
                    }
                    break;

                case 'integer':
                case 'guid':
                    if (!isset($set['operator'])) {
                        $set['operator'] = '=';
                    }
                    break;

                default:
                    throw Exceptional::Runtime(
                        'Field ' . $key . ' does not support search queries'
                    );
            }

            $this->_fields[$key] = [
                'field' => $field,
                'weight' => (int)($set['weight'] ?? 1),
                'operator' => opal\query\clause\Clause::normalizeOperator($set['operator']),
                'type' => $type
            ];
        }

        return $this;
    }

    public function getFields()
    {
        return $this->_fields;
    }

    public function getMaxScore()
    {
        $this->_prepare();

        $output = 0;
        $termCount = count($this->_terms);

        if ($termCount != 1) {
            $termCount += 1;
        }

        $weights = [];

        foreach ($this->_fields as $set) {
            if (!$this->_fieldIsUsable($set)) {
                continue;
            }

            $weights[] = $set['weight'];
        }

        if (empty($weights)) {
            return 1;
        }

        $average = array_sum($weights) / count($weights);
        $output = $average * count($weights) * $termCount;

        return $output * self::MAX_THRESHOLD_RATIO;
    }

    public function generateCaseList()
    {
        $this->_prepare();

        $output = [];
        $termCount = count($this->_terms);

        foreach ($this->_fields as $name => $set) {
            if (!$this->_fieldIsUsable($set)) {
                continue;
            }

            if ($termCount != 1) {
                $output[] = [
                    'clause' => new opal\query\clause\Clause($set['field'], $set['operator'], $this->_phrase),
                    'weight' => $set['weight'] * 2
                ];
            }

            foreach ($this->_terms as $term) {
                $output[] = [
                    'clause' => new opal\query\clause\Clause($set['field'], $set['operator'], $term),
                    'weight' => $set['weight']
                ];
            }
        }

        return $output;
    }

    public function generateWhereClauseList()
    {
        $this->_prepare();

        $output = new opal\query\clause\WhereList($this->_query, false, true);

        foreach ($this->_fields as $name => $set) {
            if (!$this->_fieldIsUsable($set)) {
                continue;
            }

            if (empty($this->_terms)) {
                $output->_addClause(new opal\query\clause\Clause(
                    $set['field'],
                    $set['operator'],
                    $this->_phrase,
                    true
                ));
            } else {
                foreach ($this->_terms as $term) {
                    $output->_addClause(new opal\query\clause\Clause(
                        $set['field'],
                        $set['operator'],
                        $term,
                        true
                    ));
                }
            }
        }

        return $output;
    }

    protected function _fieldIsUsable(array $field)
    {
        return $field['type'] == 'string' || $field['type'] == $this->_type;
    }

    protected function _prepare()
    {
        if ($this->_isPrepared) {
            return;
        }

        if ($this->_phrase === null) {
            throw Exceptional::Runtime(
                'No search phrase has been set'
            );
        }

        if (empty($this->_fields)) {
            $source = $this->_query->getSource();
            $adapter = $source->getAdapter();

            if ($adapter instanceof IIntegralAdapter) {
                $fields = [];

                foreach ($adapter->getDefaultSearchFields() as $name => $score) {
                    if (false === strpos($name, '.')) {
                        $name = $source->getAlias() . '.' . $name;
                    }

                    $fields[$name] = (int)$score;
                }

                $this->addFields($fields);
            }

            if (empty($this->_fields)) {
                throw Exceptional::Runtime(
                    'No search fields have been set'
                );
            }
        }

        /*
        $paginator = $this->_query->paginate();

        if(!$paginator->isApplied()) {
            $paginator->applyWith([]);
        }
         */

        $this->_isPrepared = true;
    }



    // Field
    public function getSource()
    {
        return $this->_query->getSource();
    }

    public function getSourceAlias()
    {
        return $this->_query->getSourceAlias();
    }

    public function getName(): string
    {
        return 'relevance';
    }

    public function getAlias()
    {
        return $this->_alias;
    }

    public function setAlias($alias)
    {
        $this->_alias = $alias;
        return $this;
    }

    public function hasDiscreetAlias()
    {
        return $this->_alias != 'relevance';
    }

    public function getQualifiedName()
    {
        return $this->getName();
    }

    public function dereference()
    {
        return [$this];
    }

    public function isOutputField()
    {
        return true;
    }


    public function rewriteAsDerived(ISource $source)
    {
        return $this;
    }

    public function toString(): string
    {
        return $this->_phrase;
    }


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'text' => $this->_phrase;
        yield 'property:*type' => $this->_type;

        if (!empty($this->_fields)) {
            foreach ($this->_fields as $name => $set) {
                yield 'value:' . $name => 'x' . $set['weight'] . ', ' . $set['operator'];
            }
        }
    }
}
