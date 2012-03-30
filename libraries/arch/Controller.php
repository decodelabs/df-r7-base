<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch;

use df;
use df\core;
use df\arch;
use df\user;

class Controller implements IController, core\IDumpable {
    
    use TContextProxy;
    use TDirectoryAccessLock;
    
    const CHECK_ACCESS = true;
    const DEFAULT_ACCESS = user\IState::NONE;
    
    protected $_type;
    protected $_activeAction;
    
    private $_isInline = false;
    
    public static function factory(IContext $context) {
        $request = $context->getRequest();
        $path = $request->getController();
        
        if(!empty($path)) {
            $parts = explode('/', $path);
        } else {
            $parts = array();
        }
            
        $type = $context->getApplication()->getRunMode();
        $parts[] = $type.'Controller';
        
        $class = 'df\\apex\\directory\\'.$request->getArea().'\\'.implode('\\', $parts);
        
        if(!class_exists($class)) {
            $class = __CLASS__;
        }
        
        return new $class($context, $type);
    }
    
    protected function __construct(arch\IContext $context, $type) {
        $this->_context = $context;
        $this->_type = $type;
        $this->_isInline = get_class($this) == __CLASS__;
    }
    
    
// Dispatch
    public function isControllerInline() {
        return $this->_isInline;
    }
    
    
// Action
    public function setActiveAction(IAction $action=null) {
        $this->_activeAction = $action;
        return $this;
    }
    
    public function getActiveAction() {
        return $this->_activeAction;
    }
    
    
// Dump
    public function getDumpProperties() {
        $runMode = $this->_context->getRunMode();
        
        if($this->_isInline) {
            $runMode .= ' (inline)';
        }
        
        return [
            'type' => $runMode,
            'activeAction' => $this->_activeAction,
            'context' => $this->_context
        ];
    }
}