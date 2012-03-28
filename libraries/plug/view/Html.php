<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug\view;

use df;
use df\core;
use df\aura;
use df\arch;

class Html implements aura\view\IHelper {
    
    protected $_view;
    
    public function __construct(aura\view\IView $view) {
        $this->_view = $view;
    }
    
    public function __call($member, $args) {
        return aura\html\widget\Base::factory($member, $args)->setRenderTarget($this->_view);
    }
    
    public function string($value) {
        return new aura\html\ElementString($value);
    }
    
    public function tag($name, array $attributes=array()) {
        return new aura\html\Tag($name, $attributes);
    }
    
    public function element($name, $content=null, array $attributes=array()) {
        return new aura\html\Element($name, $content, $attributes);
    }
}
