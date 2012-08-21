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
    
    use opal\query\TQuery_EntryPoint;
    
    protected $_context;
    
    public function __construct(archLib\IContext $context) {
        $this->_context = $context;
    }
    
    public function getContext() {
        return $this->_context;
    }
    
    
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
            ->from($source)
            ->where('@primary', '=', $primary);

        $name = $query->getSource()->getDisplayName();

        if(!$output = $query->toRow()) {
            $this->_context->throwError(404, 'Item not found - '.$name.'#'.$primary);
        }

        if(!$this->_context->user->canAccess($output, $action)) {
            $this->_context->throwError(401, 'Cannot '.$actionName.' '.$name.' items');
        }

        return $output;
    }

    
    
// Model
    public function __get($member) {
        return $this->getModel($member);
    }

    public function getModel($name) {
        return axis\Model::factory($name, $this->_context->getApplication());
    }
    
    public function getModelUnit($unitId) {
        return axis\Unit::fromId($unitId, $this->_context->getApplication());
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
