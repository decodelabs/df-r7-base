<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug\shared;

use df;
use df\core;
use df\arch as archLib;
use df\axis;
use df\opal;
use df\flex;

class Data implements core\ISharedHelper, opal\query\IEntryPoint, \ArrayAccess {
    
    use core\TSharedHelper;
    use opal\query\TQuery_EntryPoint;
    
    protected $_clusterId;
    
// Validate
    public function newValidator() {
        return new core\validate\Handler();
    }
    
    public function fetchForAction($source, $primary, $action=null, $chain=null) {
        $output = $this->_queryForAction($this->fetch()->from($source), $primary, $action, $chain);
        $this->_checkRecordAccess($output, $action);

        return $output;
    }

    public function selectForAction($source, $fields, $primary=null, $action=null, $chain=null) {
        if(!is_array($fields)) {
            $chain = $action;
            $action = $primary;
            $primary = $fields;
            $fields = ['*'];
        }

        return $this->_queryForAction($this->select($fields)->from($source), $primary, $action, $chain);
    }

    public function fetchOrCreateForAction($source, $primary, $action=null, $newChain=null, $queryChain=null) {
        if(is_callable($action)) {
            $queryChain = $newChain;
            $newChain = $action;
            $action = null;
        }

        $output = $this->_queryForAction($this->fetch()->from($source), $primary, $action, $queryChain, false);

        if($output) {
            $this->_checkRecordAccess($output, $action);
        } else {
            $output = $this->newRecord($source);

            if($newChain) {
                core\lang\Callback::factory($newChain)->invoke($output);
            }
        }

        return $output;
    }

    public function _queryForAction(opal\query\IReadQuery $query, &$primary, &$action, $chain=null, $throw=true) {
        $name = $query->getSource()->getDisplayName();
        
        if($primary === null) {
            if($throw) {
                $this->_context->throwError(404, 'Item not found - '.$name.'#NULL');
            } else {
                return null;
            }
        }

        if(is_callable($action)) {
            $chain = $action;
            $action = null;
        }

        $this->applyQueryActionClause($query, $primary);


        if($chain) {
            $query->chain($chain);
        }

        if((!$output = $query->toRow()) && $throw) {
            if(is_array($primary)) {
                $primary = implode(',', $primary);
            }

            $this->_context->throwError(404, 'Item not found - '.$name.'#'.$primary);
        }

        return $output;
    }

    protected function _checkRecordAccess($record, $action) {
        if(!$this->_context->getUserManager()->canAccess($record, $action)) {
            $actionName = $action;

            if($actionName === null) {
                $actionName = 'access';
            }

            $this->_context->throwError(401, 'Cannot '.$actionName.' '.$name.' items');
        }
    }

    public function applyQueryActionClause(opal\query\IQuery $query, $primary) {
        if(is_array($primary) && is_string(key($primary))) {
            foreach($primary as $key => $value) {
                $query->where($key, '=', $value);
            }
        } else {
            $query->where('@primary', '=', $primary);
        }

        return $this;
    }

    public function getClusterUnit() {
        try {
            return axis\Model::loadClusterUnit();
        } catch(axis\RuntimeException $e) {
            return null;
        }
    }

    public function fetchClusterRecord($clusterId) {
        $unit = axis\Model::loadClusterUnit();
        return $this->fetchForAction($unit, $clusterId);
    }

    public function newRecord($source, array $values=null) {
        $adapter = $this->_sourceToAdapter($source);
        $output = $adapter->newRecord($values);

        if(!$this->_context->getUserManager()->canAccess($output, 'add')) {
            $this->_context->throwError(401, 'Cannot add '.$source->getDisplayName().' items');
        }

        return $output;
    }

    public function newPartial($source, array $values=null) {
        return $this->_sourceToAdapter($source)->newPartial($values);
    }

    private function _sourceToAdapter($source) {
        $sourceManager = new opal\query\SourceManager();
        $source = $sourceManager->newSource($source, null);
        return $source->getAdapter();
    }

    public function newRecordTaskSet() {
        return new opal\record\task\TaskSet();
    }

    public function checkAccess($source, $action=null) {
        $actionName = $action;

        if($actionName === null) {
            $actionName = 'access';
        }
        
        $sourceManager = new opal\query\SourceManager();
        $source = $sourceManager->newSource($source, null);
        $adapter = $source->getAdapter();

        if(!$this->_context->getUserManager()->canAccess($adapter, $action)) {
            $this->_context->throwError(401, 'Cannot '.$actionName.' '.$source->getDisplayName().' items');
        }

        return $this;
    }

    public function offsetSet($offset, $value) {
        throw new \Exception('Cannot set by array access on data plug');
    }

    public function offsetGet($offset) {
        $this->_clusterId = $offset;
        return $this;
    }

    public function offsetUnset($offset) {
        $this->_clusterId = null;
        return $this;
    }

    public function offsetExists($offset) {
        return $this->_clusterId == $offset;
    }

    
    
// Model
    public function __get($member) {
        return $this->getModel($member);
    }

    public function getModel($name, $clusterId=null) {
        return axis\Model::factory($name, $this->_normalizeClusterId($clusterId));
    }
    
    public function getUnit($unitId, $clusterId=null) {
        return axis\Model::loadUnitFromId($unitId, $clusterId);
    }

    public function getSchema($unitId) {
        return axis\Model::loadUnitFromId($unitId)->getUnitSchema();
    }

    public function getSchemaField($unitId, $field) {
        return $this->getSchema($unitId)->getField($field);
    }

    protected function _normalizeClusterId($clusterId) {
        if($clusterId === null) {
            $clusterId = $this->_clusterId;
        }

        $this->_clusterId = null;
        return $clusterId;
    }


// Data helpers
    public function hasRelation($record, $field) {
        return (bool)$this->getRelationId($record, $field);
    }

    public function getRelationId($record, $field) {
        $output = null;

        if($record instanceof opal\record\IRecord) {
            $output = $record->getRawId($field);
        } else if(is_array($record)) {
            if(isset($record[$field])) {
                $output = $record[$field];
            } else {
                $output = null;
            }

            if($output instanceof opal\record\IRecord) {
                $output = $output->getPrimaryKeySet();
            }

            if($output instanceof opal\record\IPrimaryKeySet 
            && !$output->isNull()) {
                $output = $output->getValue();
            }
        }

        return $output;
    }

    public function getRelationRecord($record, $field, $allowFetch=false) {
        if(!isset($record[$field])) {
            return null;
        }
        
        if($record instanceof opal\record\IRecord && !$allowFetch) {
            $output = $record->getRaw($field);

            if($output instanceof opal\record\IPreparedValueContainer) {
                if(!$output->isPrepared()) {
                    return null;
                }

                $output = $output->getValue();
            }

        } else {
            $output = $record[$field];
        }

        if($output instanceof opal\record\IPrimaryKeySet || is_scalar($output)) {
            return null;
        }

        return $output;
    }

    public function stringToBoolean($string) {
        return core\string\Manipulator::stringToBoolean($string);
    }



// Mesh
    public function fetchEntity($locator) {
        return $this->_context->getMeshManager()->fetchEntity($locator);
    }

    public function fetchEntityForAction($id, $action=null) {
        $actionName = $action;

        if($actionName === null) {
            $actionName = 'access';
        }

        if(!$output = $this->fetchEntity($id)) {
            $this->_context->throwError(404, 'Entity not found - '.$id);
        }

        if(!$this->_context->getUserManager()->canAccess($output, $action)) {
            $this->_context->throwError(401, 'Cannot '.$actionName.' entity '.$id);
        }

        return $output;
    }



// JSON
    public function jsonEncodeCollectionQuery(opal\query\IReadQuery $query, array $extraData=null, $rowSanitizer=null) {
        if($extraData === null) {
            $extraData = [];
        }
        
        $data = $query->toArray();

        if($rowSanitizer) {
            foreach($data as $key => $row) {
                $data[$key] = $rowSanitizer->invoke($row, $key);
            }
        }

        $extraData['data'] = $data;
        $extraData['paginator'] = $query->getPaginator();
        return flex\json\Codec::encode($extraData);
    }

    public function jsonEncode($data) {
        return flex\json\Codec::encode($data);
    }

    public function jsonDecode($data) {
        return flex\json\Codec::decode($data);
    }


// Crypt
    public function hash($message, $salt=null) {
        if($salt === null) {
            $salt = $this->_context->application->getPassKey();
        }
        
        return core\string\Util::passwordHash($message, $salt);
    }
    
    public function encrypt($message, $password=null, $salt=null) {
        if($password === null) {
            $password = $this->_context->application->getPassKey();
            $salt = $this->_context->application->getUniquePrefix();
        }
        
        return core\string\Util::encrypt($message, $password, $salt);
    }
    
    public function decrypt($message, $password=null, $salt=null) {
        if($password === null) {
            $password = $this->_context->application->getPassKey();
            $salt = $this->_context->application->getUniquePrefix();
        }
        
        return core\string\Util::decrypt($message, $password, $salt);
    }
}
