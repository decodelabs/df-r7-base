<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;

class SourceManager implements ISourceManager, core\IDumpable {
    
    use core\TApplicationAware;
    use TQuery_TransactionAware;
    
    protected $_aliases = array();
    protected $_sources = array();
    protected $_adapterHashes = array();
    protected $_genCounter = 0;
    
    public function __construct(core\IApplication $application, ITransaction $transaction=null) {
        $this->_application = $application;
        $this->_transaction = $transaction;
    }
    
    public function getPolicyManager() {
        return core\policy\Manager::getInstance($this->_application);
    }
    
    public function newSource($adapter, $alias, array $fields=null, $forWrite=false) {
        $adapter = $this->extrapolateSourceAdapter($adapter);
        $sourceId = $adapter->getQuerySourceId();
        
        if($alias === null) {
            $alias = $this->generateAlias();
        }
        
        if(isset($this->_sources[$alias])) {
            throw new RuntimeException(
                'A source has already been defined with alias '.$alias
            );
        }
        
        $source = new Source($adapter, $alias);
        
        if(!isset($this->_aliases[$sourceId])) {
            $this->_aliases[$sourceId] = $alias;
        }
        
        $hash = $source->getAdapterHash();
        
        if(!isset($this->_adapterHashes[$hash])) {
            $this->_adapterHashes[$hash] = $alias;
        }
        
        $this->_sources[$alias] = $source;
        
        if($this->_transaction) {
            $this->_transaction->registerAdapter($source->getAdapter(), $forWrite);
        }
        
        
        if($fields !== null) {
            foreach($fields as $field) {
                $this->extrapolateOutputField($source, $field);
            }
        }
        
        return $source;
    }
    
    public function removeSource($alias) {
        if(!isset($this->_sources[$alias])) {
            return $this;
        }
        
        foreach($this->_aliases as $sourceId => $sourceAlias) {
            if($alias === $sourceAlias) {
                unset($this->_aliases[$sourceAlias]);
                break;
            }
        }
        
        unset($this->_sources[$alias]);
        return $this;
    }
    
    public function getSources() {
        return $this->_sources;
    }
    
    public function countSourceAdapters() {
        return count($this->_adapterHashes);
    }
    
    
    public function handleQueryException(IQuery $query, \Exception $e) {
        foreach($this->_sources as $source) {
            if($source->handleQueryException($query, $e)) {
                return true;
            }
        }
        
        return false;
    }
    
    
    
    
    
    public function extrapolateSourceAdapter($adapter) {
        if($adapter instanceof ISource) {
            $adapter = $adapter->getAdapter();
        } else if(is_string($adapter)) {
            if(isset($this->_aliases[$adapter])) {
                $adapter = $this->_sources[$this->_aliases[$adapter]]->getAdapter();
            } else {
                $entity = $this->getPolicyManager()->fetchEntity($adapter);
                
                if(!$entity instanceof IAdapter) {
                    throw new InvalidArgumentException(
                        'Entity url '.$adapter.' does not reference a valid data source adapter'
                    );
                }
                
                $adapter = $entity;
            }
        } else if(is_array($adapter)) {
            $adapter = new opal\native\QuerySourceAdapter(uniqid('source_'), $adapter);
        } else if(!$adapter instanceof IAdapter) {
            throw new InvalidArgumentException(
                'Source is not a valid adapter'
            );
        }
        
        return $adapter;
    }
    
    
    public function extrapolateOutputField(ISource $source, $name) {
        $fieldAlias = null;
        
        if(preg_match('/(.+) as (.+)$/', $name, $matches)) {
            $name = $matches[1];
            $fieldAlias = $matches[2];
        }
        
        return $this->_extrapolateField($source, $name, $fieldAlias, null, true, true, true, true);
    }

    public function extrapolateField(ISource $source, $name) {
        return $this->_extrapolateField($source, $name);
    }
    
    public function extrapolateIntrinsicField(ISource $source, $name, $checkAlias=null) {
        return $this->_extrapolateField($source, $name, null, $checkAlias, true, false, false);
    }
    
    public function extrapolateAggregateField(ISource $source, $name, $checkAlias=null) {
        return $this->_extrapolateField($source, $name, null, $checkAlias, false, false, true);
    }
    
    public function extrapolateDataField(ISource $source, $name, $checkAlias=null) {
        return $this->_extrapolateField($source, $name, null, $checkAlias, true, false, true);
    }
    
    protected function _extrapolateField(ISource $source, $name, $alias=null, $checkAlias=null, $allowIntrinsic=true, $allowWildcard=true, $allowAggregate=true, $isOutput=false) {
        if(!$isOutput && ($field = $this->_findFieldByAlias($name))) {
            $this->_testField($field, $allowIntrinsic, $allowWildcard, $allowAggregate);
            return $field;
        }
            
        $passedSourceAlias = $source->getAlias();
            
        if(preg_match('/^([a-zA-Z_]+)\((.+)\)$/', $name, $matches)) {
            if(!$allowAggregate) {
                throw new InvalidArgumentException(
                    'Aggregate field reference "'.$name.'" found when intrinsic field expected'
                );
            } else {
                if(!$source->getAdapter()->supportsQueryFeature(opal\query\IQueryFeatures::AGGREGATE)) {
                    throw new LogicException(
                        'Query adapter '.$source->getAdapter()->getQuerySourceDisplayName().' '.
                        'does not support aggregate fields'
                    );
                }
            }
            
            // aggregate
            $type = $matches[1];
            $targetField = $this->extrapolateField($source, $matches[2]);
            
            if($checkAlias === true && $passedSourceAlias !== $targetField->getSourceAlias()) {
                throw new InvalidArgumentException(
                    'Source alias "'.$sourceAlias.'" found when alias "'.$source->getAlias().'" is expected'
                );
            } else if(is_string($checkAlias) && $targetField->getSourceAlias() == $checkAlias) {
                throw new InvalidArgumentException(
                    'Local source reference "'.$checkAlias.'" found where a foreign source is expected'
                );
            }
            
            
            $qName = $type.'('.$targetField->getQualifiedName().')';
            
            if(!$isOutput && ($field = $source->getFieldByQualifiedName($qName))) {
                $this->_testField($field, $allowIntrinsic, $allowWildcard, $allowAggregate);
                return $field;
            }
            
            $field = new opal\query\field\Aggregate($source, $type, $targetField, $alias);
            
            if($isOutput) {
                $source->addOutputField($field);
            } else {
                $source->addPrivateField($field);
            }
            
            return $field;
        } else {
            if(false !== strpos($name, '.')) {
                // qualified
                $qName = $name;
                list($sourceAlias, $name) = explode('.', $name, 2);
            } else {
                // local
                $sourceAlias = $source->getAlias();
                $qName = $sourceAlias.'.'.$name;
            }
            
            if(!isset($this->_sources[$sourceAlias])) {
                throw new InvalidArgumentException(
                    'Source alias "'.$sourceAlias.'" has not been defined'
                );
            }
            
            $source = $this->_sources[$sourceAlias];
            
            if($checkAlias === true && $passedSourceAlias !== $source->getAlias()) {
                throw new InvalidArgumentException(
                    'Source alias "'.$passedSourceAlias.'" found when alias "'.$source->getAlias().'" is expected'
                );
            } else if(is_string($checkAlias) && $source->getAlias() == $checkAlias) {
                throw new InvalidArgumentException(
                    'Local source reference "'.$checkAlias.'" found where a foreign source is expected'
                );
            }
            
            if(!$isOutput && ($field = $source->getFieldByQualifiedName($qName))) {
                $this->_testField($field, $allowIntrinsic, $allowWildcard, $allowAggregate);
                return $field;
            }
           
            if($name == '*') {
                if(!$allowWildcard) {
                    throw new InvalidArgumentException(
                        'Unexpected wildcard field reference "'.$qName.'"'
                    );
                }
                
                $field = new opal\query\field\Wildcard($source);
            } else {
                if(!$allowIntrinsic) {
                    throw new InvalidArgumentException(
                        'Unexpected intrinsic field reference "'.$qName.'"'
                    );
                }
                
                if($alias === null) {
                    $alias = $name;
                }
                
                // If adapter supports virtuals, give it a chance to dereference it from the alias
                $adapter = $source->getAdapter();
                $field = null;
                
                if($adapter instanceof IIntegralAdapter) {
                    $field = $adapter->extrapolateQuerySourceField($source, $name, $alias);
                }
                
                if(!$field) {
                    $field = new opal\query\field\Intrinsic($source, $name, $alias);
                }
            }
            
            if($isOutput) {
                $source->addOutputField($field);
            } else {
                $source->addPrivateField($field);
            }
            
            return $field;
        }
    }
    
    protected function _findFieldByAlias($alias) {
        foreach($this->_sources as $source) {
            if($field = $source->getFieldByAlias($alias)) {
                return $field;
            }
        }
        
        return null;
    }

    protected function _testField(opal\query\IField $field, $allowIntrinsic, $allowWildcard, $allowAggregate) {
        if(!$allowIntrinsic && $field instanceof opal\query\IIntrinsicField) {
            throw new InvalidArgumentException(
                'Unexpected intrinsic field reference "'.$field->getQualifiedName().'"'
            );
        } else if(!$allowWildcard && $field instanceof opal\query\IWildcardField) {
            throw new InvalidArgumentException(
                'Unexpected wildcard field reference "'.$field->getQualifiedName().'"'
            );
        } else if(!$allowAggregate && $field instanceof opal\query\IAggregateField) {
            throw new InvalidArgumentException(
                'Aggregate field reference to "'.$field->getQualifiedName().'" found when intrinsic field expected'
            );
        }
    }
    
    
    public function generateAlias() {
        do {
            $alias = core\string\Manipulator::numericToAlpha($this->_genCounter++);
        } while(isset($this->_aliases[$alias]));
        
        return $alias;
    }
    
    
// Dump
    public function getDumpProperties() {
        $output = array();
        
        foreach($this->_sources as $alias => $source) {
            $output[$alias] = $source->getAdapter();
        }
        
        return $output;
    }
}
