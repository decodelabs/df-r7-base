<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch;

use df;
use df\core;
use df\arch;

class Controller implements IController, core\IDumpable {
    
    use core\TContextProxy;
    use TResponseForcer;
    
    protected $_type;
    private $_isInline = false;
    
    public static function factory(IContext $context) {
        $runMode = $context->getRunMode();
        $class = self::getClassFor($context->location, $runMode);
        
        if(!$class) {
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
        $class = 'df\\apex\\directory\\'.$request->getArea().'\\'.implode('\\', $parts);

        if(!class_exists($class)) {
            $class = null;
        }

        return $class;
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
    
    
// Dump
    public function getDumpProperties() {
        $runMode = $this->_context->getRunMode();
        
        if($this->_isInline) {
            $runMode .= ' (inline)';
        }
        
        return [
            'type' => $runMode,
            'context' => $this->_context
        ];
    }
}