<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug\context;

use df;
use df\core;
use df\arch as archLib;
use df\axis;
use df\opal;

class Data implements archLib\IContextHelper, opal\query\IEntryPoint {
    
    use archLib\TContextHelper;
    use opal\query\TQuery_EntryPoint;
    
    
// Validate
    public function newValidator() {
        return new core\validate\Handler();
    }
    
    
// Query
    private function _getEntryPointApplication() {
        return $this->_context->getApplication();
    }
    

    public function fetchForAction($source, $primary, $action=null) {
        $actionName = $action;

        if($actionName === null) {
            $actionName = 'access';
        }

        $query = $this->fetch()
            ->from($source);

        if(is_array($primary) && reset($primary) && is_string(key($primary))) {
            foreach($primary as $key => $value) {
                $query->where($key, '=', $value);
            }

            $primary = implode(',', $primary);
        } else {
            $query->where('@primary', '=', $primary);
        }

        $name = $query->getSource()->getDisplayName();

        if(!$output = $query->toRow()) {
            $this->_context->throwError(404, 'Item not found - '.$name.'#'.$primary);
        }

        if(!$this->_context->user->canAccess($output, $action)) {
            $this->_context->throwError(401, 'Cannot '.$actionName.' '.$name.' items');
        }

        return $output;
    }

    public function newRecord($source, array $values=null) {
        $sourceManager = new opal\query\SourceManager($this->_context->getApplication());
        $source = $sourceManager->newSource($source, null);
        $adapter = $source->getAdapter();

        $output = $adapter->newRecord($values);

        if(!$this->_context->user->canAccess($output, 'add')) {
            $this->throwError(401, 'Cannot add '.$source->getDisplayName().' items');
        }

        return $output;
    }

    public function checkAccess($source, $action=null) {
        $actionName = $action;

        if($actionName === null) {
            $actionName = 'access';
        }
        
        $sourceManager = new opal\query\SourceManager($this->_context->getApplication());
        $source = $sourceManager->newSource($source, null);
        $adapter = $source->getAdapter();

        if(!$this->_context->user->canAccess($adapter, $action)) {
            $this->throwError(401, 'Cannot '.$actionName.' '.$source->getDisplayName().' items');
        }

        return $this;
    }

    
    
// Model
    public function __get($member) {
        return $this->getModel($member);
    }

    public function getModel($name) {
        return axis\Model::factory($name, $this->_context->getApplication());
    }
    
    public function getUnit($unitId) {
        return axis\Unit::fromId($unitId, $this->_context->getApplication());
    }

    public function getSchema($unitId) {
        return axis\Unit::fromId($unitId, $this->_context->getApplication())->getUnitSchema();
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
                $value = $record[$field];
            } else {
                $value = null;
            }

            if($value instanceof opal\record\IRecord) {
                $value = $value->getPrimaryManifest();
            }

            if($value instanceof opal\record\IPrimaryManifest 
            && !$value->isNull()) {
                $output = $value->getValue();
            }
        }

        return $output;
    }



// Policy
    public function fetchEntity($locator) {
        return $this->_context->policy->fetchEntity($locator);
    }

    public function fetchEntityForAction($id, $action=null) {
        $actionName = $action;

        if($actionName === null) {
            $actionName = 'access';
        }

        if(!$output = $this->fetchEntity($id)) {
            $this->_context->throwError(404, 'Entity not found - '.$id);
        }

        if(!$this->_context->user->canAccess($output, $action)) {
            $this->_context->throwError(401, 'Cannot '.$actionName.' entity '.$id);
        }

        return $output;
    }


// Crypt
    public function hash($message, $salt=null) {
        if($salt === null) {
            $salt = $this->_context->getApplication()->getPassKey();
        }
        
        return core\string\Util::passwordHash($message, $salt);
    }
    
    public function encrypt($message, $password=null, $salt=null) {
        if($password === null) {
            $application = $this->_context->getApplication();
            $password = $application->getPassKey();
            $salt = $application->getUniquePrefix();
        }
        
        return core\string\Util::encrypt($message, $password, $salt);
    }
    
    public function decrypt($message, $password=null, $salt=null) {
        if($password === null) {
            $application = $this->_context->getApplication();
            $password = $application->getPassKey();
            $salt = $application->getUniquePrefix();
        }
        
        return core\string\Util::decrypt($message, $password, $salt);
    }
}
