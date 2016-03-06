<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;

class Union implements IUnionQuery {

    use TQuery;
    use TQuery_Derivable;
    use TQuery_Attachable;
    use TQuery_Combinable;
    use TQuery_Orderable;
    use TQuery_Limitable;
    use TQuery_Offsettable;
    use TQuery_Pageable;
    use TQuery_Read;
    use TQuery_SelectSourceDataFetcher;

    protected $_sourceManager;
    protected $_primaryQuery;
    protected $_queries = [];

    public function __construct(ISourceManager $sourceManager) {
        $this->_sourceManager = $sourceManager;
    }

    public function getQueryType() {
        return IQueryType::UNION;
    }

    public function getSourceManager() {
        return $this->_sourceManager;
    }

    public function getSource() {
        if(empty($this->_queries)) {
            throw new LogicException(
                'Union has no child queries yet!'
            );
        }

        return $this->_queries[0]->getSource();
    }

    public function getSourceAlias() {
        return $this->getSource()->getSourceAlias();
    }



    public function with(...$fields) {
        return Initiator::factory()
            ->beginUnionSelect($this, $fields, true);
    }

    public function withAll(...$fields) {
        return Initiator::factory()
            ->beginUnionSelect($this, $fields, false);
    }

    public function addQuery(IUnionSelectQuery $query) {
        if(!in_array($query, $this->_queries, true)) {
            if(empty($this->_queries)) {
                $this->_primaryQuery = $query;
            } else {
                $primarySource = $this->_primaryQuery->getSource();
                $newSource = $query->getSource();

                if($newSource->getHash() != $primarySource->getHash()) {
                    throw new LogicException(
                        'Union queries must all be on the same adapter'
                    );
                }

                $newFields = array_values($query->getOutputFields());
                $i = 0;

                foreach($this->_primaryQuery->getOutputFields() as $name => $field) {
                    if($field instanceof IExpressionField && $field->isNull() && isset($newFields[$i])) {
                        $newField = $newFields[$i];

                        if(!$newField instanceof IExpressionField || !$field->isNull()) {
                            $field->setAlias($newField->getAlias())
                                ->setAltSourceAlias($newField->getSourceAlias());
                        }
                    }

                    $i++;
                }
            }

            $this->_queries[] = $query;
        }

        return $this;
    }

    public function getOutputManifest() {
        $output = new OutputManifest($this->getSource());

        if($this->_primaryQuery instanceof IJoinProviderQuery) {
            foreach($this->_primaryQuery->getJoins() as $join) {
                $output->importSource($join->getSource());
            }
        }

        return $output;
    }

    public function getQueries() {
        return $this->_queries;
    }

    public function count() {
        return $this->_sourceManager->executeQuery($this, function($adapter) {
            return (int)$adapter->countUnionQuery($this);
        });
    }
}