<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\view\content;

use df;
use df\core;
use df\aura;
use df\arch;
    
class SlotRenderer implements aura\view\IDeferredRenderable {

    use aura\view\TDeferredRenderable;
    use core\TStringProvider;

    const TYPE_VALUE = 'value';
    const TYPE_CALLBACK = 'callback';
    const TYPE_TEMPLATE = 'template';

    protected $_value;
    protected $_location;
    protected $_args;

    public static function factory($value) {
        return self::factoryArgs(func_get_args());
    }

    public static function factoryArgs(array $args) {
        if(count($args) == 1) {
            $value = array_shift($args);

            if(is_callable($value)) {
                $type = self::TYPE_CALLBACK;
            } else {
                $type = self::TYPE_VALUE;
            }

            $location = null;
            $args = null;
        } else {
            $type = self::TYPE_TEMPLATE;
            $value = array_shift($args);
            $location = array_shift($args);
            $args = array_shift($args);

            if($args !== null && !is_array($args)) {
                $args = (array)$args;
            }
        }

        return new self($type, $value, $location, $args);
    }

    protected function __construct($type, $value, $location=null, array $args=null) {
        $this->_type = $type;
        $this->_value = $value;
        $this->_location = $location ? arch\Request::factory($location) : null;
        $this->_args = $args ? $args : array();
    }

    public function getType() {
        return $this->_type;
    }

    public function getValue() {
        return $this->_value;
    }

    public function getLocation() {
        return $this->_location;
    }

    public function getArgs() {
        return $this->_args;
    }

    public function render() {
        switch($this->_type) {
            case self::TYPE_VALUE:
                return $this->_value;

            case self::TYPE_CALLBACK:
                return call_user_func_array($this->_value, [$this->getView()]);

            case self::TYPE_TEMPLATE:
                try {
                    $view = $this->getView();
                    $context = $view->getContext()->spawnInstance($this->_location);
                    $template = aura\view\content\Template::loadDirectoryTemplate($context, $this->_value);
                    $template->setRenderTarget($view);
                
                    return $template;
                } catch(\Exception $e) {
                    return $view->newErrorContainer($e);
                }
        }
    }

    public function toString() {
        return $this->render();
    }
}