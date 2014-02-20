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

class Data implements core\ISharedHelper, opal\query\IEntryPoint {
    
    use core\TSharedHelper;
    use opal\query\TQuery_EntryPoint;
    
    
// Validate
    public function newValidator() {
        return new core\validate\Handler();
    }
    
    
// Query
    private function _getEntryPointApplication() {
        return $this->_context->application;
    }
    

    public function fetchForAction($source, $primary, $action=null) {
        $actionName = $action;

        if($actionName === null) {
            $actionName = 'access';
        }

        $query = $this->fetch()->from($source);
        $this->applyQueryActionClause($query, $primary);

        $name = $query->getSource()->getDisplayName();

        if(!$output = $query->toRow()) {
            if(is_array($primary)) {
                $primary = implode(',', $primary);
            }

            $this->_context->throwError(404, 'Item not found - '.$name.'#'.$primary);
        }

        if(!$this->_context->getUserManager()->canAccess($output, $action)) {
            $this->_context->throwError(401, 'Cannot '.$actionName.' '.$name.' items');
        }

        return $output;
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

    public function newRecord($source, array $values=null) {
        $sourceManager = new opal\query\SourceManager($this->_context->application);
        $source = $sourceManager->newSource($source, null);
        $adapter = $source->getAdapter();

        $output = $adapter->newRecord($values);

        if(!$this->_context->getUserManager()->canAccess($output, 'add')) {
            $this->_context->throwError(401, 'Cannot add '.$source->getDisplayName().' items');
        }

        return $output;
    }

    public function newRecordTaskSet() {
        return new opal\record\task\TaskSet($this->_context->application);
    }

    public function checkAccess($source, $action=null) {
        $actionName = $action;

        if($actionName === null) {
            $actionName = 'access';
        }
        
        $sourceManager = new opal\query\SourceManager($this->_context->application);
        $source = $sourceManager->newSource($source, null);
        $adapter = $source->getAdapter();

        if(!$this->_context->getUserManager()->canAccess($adapter, $action)) {
            $this->_context->throwError(401, 'Cannot '.$actionName.' '.$source->getDisplayName().' items');
        }

        return $this;
    }

    
    
// Model
    public function __get($member) {
        return $this->getModel($member);
    }

    public function getModel($name) {
        return axis\Model::factory($name, $this->_context->application);
    }
    
    public function getUnit($unitId) {
        return axis\Model::loadUnitFromId($unitId, $this->_context->application);
    }

    public function getSchema($unitId) {
        return axis\Model::loadUnitFromId($unitId, $this->_context->application)->getUnitSchema();
    }

    public function getSchemaField($unitId, $field) {
        return $this->getSchema($unitId)->getField($field);
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



// Policy
    public function fetchEntity($locator) {
        return $this->_context->getPolicyManager()->fetchEntity($locator);
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
    public function jsonEncodeCollectionQuery(opal\query\IReadQuery $query, array $extraData=null, Callable $rowSanitizer=null) {
        if($extraData === null) {
            $extraData = [];
        }
        
        $data = $query->toArray();

        if($rowSanitizer) {
            foreach($data as $key => $row) {
                $data[$key] = $rowSanitizer->__invoke($row, $key);
            }
        }

        $extraData['data'] = $data;
        $extraData['paginator'] = $query->getPaginator();
        return $this->jsonEncode($extraData);
    }

    public function jsonEncode($data) {
        return json_encode($this->_prepareJsonData($data));
    }

    protected function _prepareJsonData($data) {
        if(is_scalar($data)) {
            return $data;
        }

        if($data instanceof core\time\IDate) {
            return $data->format(core\time\Date::W3C);
        }

        if($data instanceof core\IArrayProvider) {
            $data = $data->toArray();
        }

        if(!is_array($data)) {
            if(method_exists($data, '__toString')) {
                return (string)$data;
            }

            return $data;
        }

        foreach($data as $key => $value) {
            $data[$key] = $this->_prepareJsonData($value);
        }

        return $data;
    }

    public function jsonDecode($data) {
        return json_decode($data);
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
