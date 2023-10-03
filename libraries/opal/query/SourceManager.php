<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\opal\query;

use DecodeLabs\Dictum;
use DecodeLabs\Exceptional;

use DecodeLabs\Glitch\Dumpable;
use df\mesh;
use df\opal;

class SourceManager implements ISourceManager, Dumpable
{
    protected $_parent;
    protected $_aliases = [];
    protected $_sources = [];
    protected $_adapterHashes = [];
    protected $_serverHashes = [];
    protected $_genCounter = 0;
    protected $_transaction;

    public function __construct(ITransaction $transaction = null)
    {
        $this->_transaction = $transaction;
    }

    public function getMeshManager()
    {
        return mesh\Manager::getInstance();
    }

    public function setParentSourceManager(ISourceManager $parent)
    {
        $this->_parent = $parent;
        return $this;
    }

    public function getParentSourceManager()
    {
        return $this->_parent;
    }


    // Transaction
    public function setTransaction(mesh\job\ITransaction $transaction = null)
    {
        $this->_transaction = $transaction;

        if ($this->_parent) {
            $this->_parent->setTransaction($transaction);
        }

        return $this;
    }

    public function getTransaction(): ?mesh\job\ITransaction
    {
        if ($this->_parent) {
            if ($output = $this->_parent->getTransaction()) {
                return $output;
            }
        }

        return $this->_transaction;
    }


    // Sources
    public function newSource($adapter, $alias, array $fields = null, $forWrite = false, $debug = false)
    {
        $adapter = $this->extrapolateSourceAdapter($adapter);
        $sourceId = $adapter->getQuerySourceId();

        if ($alias === null) {
            $alias = $this->generateAlias();
        }

        if (isset($this->_sources[$alias])) {
            if ($adapter->getQuerySourceId() == $this->_sources[$alias]->getAdapter()->getQuerySourceId()) {
                $output = $this->_sources[$alias];

                if ($fields !== null) {
                    foreach ($fields as $field) {
                        $this->extrapolateOutputField($output, $field);
                    }
                }

                return $output;
            }

            throw Exceptional::Runtime(
                'A source has already been defined with alias ' . $alias
            );
        }

        $source = new Source($adapter, $alias);

        if (!isset($this->_aliases[$sourceId])) {
            $this->_aliases[$sourceId] = $alias;
        }

        $hash = $source->getAdapterHash();

        if (!isset($this->_adapterHashes[$hash])) {
            $this->_adapterHashes[$hash] = $alias;
        }

        $this->_serverHashes[$source->getAdapterServerHash()] = true;

        $this->_sources[$alias] = $source;

        if ($this->_transaction) {
            $this->_transaction->registerAdapter($source->getAdapter());
        }

        if ($fields !== null) {
            foreach ($fields as $field) {
                $this->extrapolateOutputField($source, $field);
            }
        }

        return $source;
    }

    public function removeSource($alias)
    {
        if (!isset($this->_sources[$alias])) {
            return $this;
        }

        foreach ($this->_aliases as $sourceId => $sourceAlias) {
            if ($alias === $sourceAlias) {
                unset($this->_aliases[$sourceAlias]);
                break;
            }
        }

        unset($this->_sources[$alias]);
        return $this;
    }

    public function getSources()
    {
        return $this->_sources;
    }

    public function getSourceByAlias($alias)
    {
        if (isset($this->_sources[$alias])) {
            return $this->_sources[$alias];
        }

        if ($this->_parent) {
            return $this->_parent->getSourceByAlias($alias);
        }
    }

    public function countSourceAdapters()
    {
        return count($this->_adapterHashes);
    }

    public function canQueryLocally()
    {
        return count($this->_serverHashes) == 1;
    }



    // Extrapolate
    public function extrapolateSourceAdapter($adapter)
    {
        if ($adapter instanceof ISource) {
            $adapter = $adapter->getAdapter();
        } elseif (is_string($adapter)) {
            if (isset($this->_aliases[$adapter])) {
                $adapter = $this->_sources[$this->_aliases[$adapter]]->getAdapter();
            } else {
                $entity = $this->getMeshManager()->fetchEntity($adapter);

                if (!$entity instanceof IAdapter) {
                    throw Exceptional::InvalidArgument(
                        'Entity url ' . $adapter . ' does not reference a valid data source adapter'
                    );
                }

                $adapter = $entity;
            }
        } elseif (is_array($adapter)) {
            $adapter = new opal\native\QuerySourceAdapter(uniqid('source_'), $adapter);
        } elseif (!$adapter instanceof IAdapter) {
            throw Exceptional::InvalidArgument(
                'Source is not a valid adapter'
            );
        }

        return $adapter;
    }


    public function extrapolateOutputField(ISource $source, $name)
    {
        $fieldAlias = null;

        if (preg_match('/(.+) as ([^ ]+)$/', (string)$name, $matches)) {
            $name = $matches[1];
            $fieldAlias = $matches[2];
        }

        return $this->_extrapolateField($source, $name, $fieldAlias, null, true, true, true, true);
    }

    public function realiasOutputField(ISource $parentSource, ISource $source, $name)
    {
        $alias = $name;
        $sourceAlias = null;

        if (preg_match('/(.+) as ([^ ]+)$/', (string)$name, $matches)) {
            $name = $matches[1];
            $alias = $matches[2];
        }

        if (preg_match('/(.+)\.(.+)$/', (string)$name, $matches)) {
            $sourceAlias = $matches[1];
            $name = $matches[2];
        }

        if ($sourceAlias && !isset($this->_sources[$sourceAlias])) {
            throw Exceptional::InvalidArgument(
                'No source has been defined with alias ' . $sourceAlias
            );
        }

        if (!$field = $this->_findFieldByAlias($name, $parentSource, null, $sourceAlias)) {
            $targetSource = $sourceAlias ? $this->_sources[$sourceAlias] : $parentSource;
            $field = $this->_extrapolateField($targetSource, $name, null, null, true, true, true, true);
        }

        return new opal\query\field\Virtual($source, $name, $alias, [$field]);
    }

    public function extrapolateField(ISource $source, $name)
    {
        return $this->_extrapolateField($source, $name);
    }

    public function extrapolateIntrinsicField(ISource $source, $name, $checkAlias = null)
    {
        return $this->_extrapolateField($source, $name, null, $checkAlias, true, false, false);
    }

    public function extrapolateAggregateField(ISource $source, $name, $checkAlias = null)
    {
        return $this->_extrapolateField($source, $name, null, $checkAlias, false, false, true);
    }

    public function extrapolateDataField(ISource $source, $name, $checkAlias = null)
    {
        return $this->_extrapolateField($source, $name, null, $checkAlias, true, false, true);
    }

    protected function _extrapolateField(ISource $source, $name, $alias = null, $checkAlias = null, $allowIntrinsic = true, $allowWildcard = true, $allowAggregate = true, $isOutput = false)
    {
        if ($name instanceof opal\query\IQuery) {
            if (!$allowIntrinsic) {
                throw Exceptional::InvalidArgument(
                    'Unexpected intrinsic sub-select query field'
                );
            }

            return $name;
        }

        if ($name instanceof opal\query\IField) {
            $name = $name->getQualifiedName();
        }

        if (!strlen((string)$name)) {
            $name = null;
        }

        if (!$isOutput && $name !== null && ($field = $this->_findFieldByAlias($name, $source, $checkAlias))) {
            $this->_testField($field, $allowIntrinsic, $allowWildcard, $allowAggregate);
            return $field;
        }

        $passedSourceAlias = $source->getAlias();

        if (preg_match('/^([a-zA-Z_]+)\((distinct )?(.+)\)$/i', (string)$name, $matches)) {
            // aggregate
            if (!$allowAggregate) {
                throw Exceptional::InvalidArgument(
                    'Aggregate field reference "' . $name . '" found when intrinsic field expected'
                );
            } else {
                if (!$source->getAdapter()->supportsQueryFeature(opal\query\IQueryFeatures::AGGREGATE)) {
                    throw Exceptional::Logic(
                        'Query adapter ' . $source->getAdapter()->getQuerySourceDisplayName() . ' ' .
                        'does not support aggregate fields'
                    );
                }
            }

            $type = $matches[1];
            $distinct = !empty($matches[2]);
            $targetField = $this->extrapolateField($source, $matches[3]);

            if ($checkAlias === true && $passedSourceAlias !== $targetField->getSourceAlias()) {
                throw Exceptional::InvalidArgument(
                    'Source alias "' . $passedSourceAlias . '" found when alias "' . $source->getAlias() . '" is expected'
                );
            } elseif (is_string($checkAlias) && $targetField->getSourceAlias() == $checkAlias) {
                throw Exceptional::InvalidArgument(
                    'Local source reference "' . $checkAlias . '" found where a foreign source is expected'
                );
            }


            $qName = $type . '(' . $targetField->getQualifiedName() . ')';

            if (!$isOutput && ($field = $source->getFieldByQualifiedName($qName))) {
                $this->_testField($field, $allowIntrinsic, $allowWildcard, $allowAggregate);
                return $field;
            }

            $field = new opal\query\field\Aggregate($source, $type, $targetField, $alias);
            $field->isDistinct($distinct);

            if ($isOutput) {
                $source->addOutputField($field);
            } else {
                $source->addPrivateField($field);
            }

            return $field;
        } elseif ($name === null || substr($name, 0, 1) == '#') {
            // expression
            $field = new opal\query\field\Expression($source, $name, $alias);

            if ($isOutput) {
                $source->addOutputField($field);
            } else {
                $source->addPrivateField($field);
            }


            return $field;
        } else {
            $shouldCheck = true;

            if (substr($name, 0, 5) == '@raw ') {
                // raw - fudge it!
                $sourceAlias = $source->getAlias();
                $qName = $sourceAlias . '.' . $name;
            } elseif (false !== strpos($name, '.')) {
                // qualified
                $qName = $name;
                list($sourceAlias, $name) = explode('.', $name, 2);
                $shouldCheck = false;
            } else {
                // local
                $sourceAlias = $source->getAlias();
                $qName = $sourceAlias . '.' . $name;
            }

            $source = null;

            if (isset($this->_sources[$sourceAlias])) {
                $source = $this->_sources[$sourceAlias];
            } elseif ($this->_parent) {
                $source = $this->_parent->getSourceByAlias($sourceAlias);
            }

            if (!$source) {
                throw Exceptional::InvalidArgument(
                    'Source alias "' . $sourceAlias . '" has not been defined'
                );
            }

            if ($checkAlias === true && $shouldCheck && $passedSourceAlias !== $source->getAlias()) {
                throw Exceptional::InvalidArgument(
                    'Source alias "' . $passedSourceAlias . '" found when alias "' . $source->getAlias() . '" is expected'
                );
            } elseif (is_string($checkAlias) && $source->getAlias() == $checkAlias) {
                throw Exceptional::InvalidArgument(
                    'Local source reference "' . $checkAlias . '" found where a foreign source is expected'
                );
            }

            if (!$isOutput && ($field = $source->getFieldByQualifiedName($qName))) {
                $this->_testField($field, $allowIntrinsic, $allowWildcard, $allowAggregate);
                return $field;
            }

            if (substr($name, 0, 1) == '!') {
                if (!$allowWildcard || $name == '!*') {
                    throw Exceptional::InvalidArgument(
                        'Unexpected wildcard field reference "' . $qName . '"'
                    );
                }

                $name = substr($name, 1);

                if ($wildcard = $source->getWildcardField()) {
                    $wildcard->addMuteField($name, $alias);
                } elseif (!$source->removeWildcardOutputField($name, $alias)) {
                    $wildcard = new opal\query\field\Wildcard($source);
                    $wildcard->addMuteField($name, $alias);
                    $source->addOutputField($wildcard);
                }

                return $wildcard;
            }

            if ($name == '@void') {
                $field = null;
            } elseif ($name == '*') {
                if (!$allowWildcard) {
                    throw Exceptional::InvalidArgument(
                        'Unexpected wildcard field reference "' . $qName . '"'
                    );
                }

                $field = new opal\query\field\Wildcard($source);
            } else {
                if (!$allowIntrinsic) {
                    throw Exceptional::InvalidArgument(
                        'Unexpected intrinsic field reference "' . $qName . '"'
                    );
                }


                // If adapter supports virtuals, give it a chance to dereference it from the alias
                $field = $source->extrapolateIntegralAdapterField($name, $alias);

                if (!$field) {
                    if ($alias === null) {
                        $alias = $name;
                    }

                    $field = new opal\query\field\Intrinsic($source, $name, $alias);
                }
            }

            if ($field) {
                if ($isOutput) {
                    $source->addOutputField($field);
                } else {
                    $source->addPrivateField($field);
                }
            }

            return $field;
        }
    }

    protected function _findFieldByAlias($alias, ISource $source, $checkAlias = null, $sourceAlias = null)
    {
        if ($sourceAlias !== null && isset($this->_sources[$sourceAlias])) {
            if ($field = $this->_sources[$sourceAlias]->getFieldByAlias($alias)) {
                return $field;
            }
        }

        if ($field = $source->getFieldByAlias($alias)) {
            return $field;
        }

        if ($checkAlias) {
            return null;
        }

        $sourceId = $source->getId();
        $keySources = [];

        foreach ($this->_sources as $testSource) {
            if ($testSource->getId() == $sourceId) {
                if ($testSource !== $source) {
                    $keySources[] = $testSource;
                }

                continue;
            }

            if ($field = $testSource->getFieldByAlias($alias)) {
                return $field;
            }
        }

        if (!empty($keySources)) {
            foreach ($keySources as $testSource) {
                if ($field = $testSource->getFieldByAlias($alias)) {
                    return $field;
                }
            }
        }

        return null;
    }

    protected function _testField(opal\query\IField $field, $allowIntrinsic, $allowWildcard, $allowAggregate)
    {
        if (!$allowIntrinsic && $field instanceof opal\query\IIntrinsicField) {
            throw Exceptional::InvalidArgument(
                'Unexpected intrinsic field reference "' . $field->getQualifiedName() . '"'
            );
        } elseif (!$allowWildcard && $field instanceof opal\query\IWildcardField) {
            throw Exceptional::InvalidArgument(
                'Unexpected wildcard field reference "' . $field->getQualifiedName() . '"'
            );
        } elseif (!$allowAggregate && $field instanceof opal\query\IAggregateField) {
            throw Exceptional::InvalidArgument(
                'Aggregate field reference to "' . $field->getQualifiedName() . '" found when intrinsic field expected'
            );
        }
    }


    public function generateAlias()
    {
        do {
            $alias = Dictum::numericToAlpha($this->_genCounter++);
        } while (isset($this->_aliases[$alias]));

        return $alias;
    }


    // Query executor
    public function handleQueryException(IQuery $query, \Throwable $e)
    {
        foreach ($this->_sources as $source) {
            if ($source->handleQueryException($query, $e)) {
                return true;
            }
        }

        return false;
    }

    public function executeQuery(IQuery $query, callable $executor)
    {
        $adapter = $query->getSource()->getAdapter();
        $count = 0;
        $exceptions = [];

        while (true) {
            try {
                return $executor($adapter);
            } catch (\Throwable $e) {
                $exceptions[] = $e;
                $handled = false;

                foreach ($this->_sources as $source) {
                    if ($source->handleQueryException($query, $e)) {
                        $handled = true;
                        break;
                    }
                }

                if (!$handled) {
                    throw $e;
                }
            }

            $count++;

            if ($count > 20) {
                throw Exceptional::Runtime(
                    'Stuck in query exception loop'
                );
            }
        }
    }


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        if ($this->_parent) {
            yield 'property:*parent' => $this->_parent;
        }

        foreach ($this->_sources as $alias => $source) {
            yield 'value:' . $alias => $source->getAdapter();
        }
    }
}
