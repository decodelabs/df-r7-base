<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug\context;

use df;
use df\core;
use df\arch;
use df\axis;
use df\opal;

class Data implements arch\IContextHelper, opal\query\IEntryPoint {
    
    protected $_context;
    
    public function __construct(arch\IContext $context) {
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
    public function select($field1=null) {
        return opal\query\Initiator::factory($this->_context->getApplication())->beginSelect(func_get_args());
    }
    
    public function fetch() {
        return opal\query\Initiator::factory($this->_context->getApplication())->beginFetch();
    }
    
    public function insert($row) {
        return opal\query\Initiator::factory($this->_context->getApplication())->beginInsert($row);
    }
    
    public function batchInsert($rows=array()) {
        return opal\query\Initiator::factory($this->_context->getApplication())->beginBatchInsert($rows);
    }
    
    public function replace($row) {
        return opal\query\Initiator::factory($this->_context->getApplication())->beginReplace($row);
    }
    
    public function batchReplace($rows=array()) {
        return opal\query\Initiator::factory($this->_context->getApplication())->beginBatchReplace($rows);
    }
    
    public function update(array $valueMap=null) {
        return opal\query\Initiator::factory($this->_context->getApplication())->beginUpdate($valueMap);
    }
    
    public function delete() {
        return opal\query\Initiator::factory($this->_context->getApplication())->beginDelete();
    }
    
    public function begin() {
        return new opal\query\Transaction($this->_context->getApplication());
    }
    
    
    
// Model
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
