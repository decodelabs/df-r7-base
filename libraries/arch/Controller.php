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
        $runMode = $context->getApplication()->getRunMode();

        $class = self::getClassFor(
            $context->getRequest(), 
            $runMode
        );
        
        if(!class_exists($class)) {
            $class = __CLASS__;
        }
        
        return new $class($context, $runMode);
    }

    public static function getClassFor(IRequest $request, $runMode='Http') {
        $runMode = ucfirst($runMode);
        $path = $request->getController();
        
        if(!empty($path)) {
            $parts = explode('/', $path);
        } else {
            $parts = array();
        }
        
        $parts[] = $runMode.'Controller';
        
        return 'df\\apex\\directory\\'.$request->getArea().'\\'.implode('\\', $parts);
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