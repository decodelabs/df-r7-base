<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\theme\facet;

use df;
use df\core;
use df\aura;
use df\arch;

abstract class Base implements aura\theme\IFacet {
    
    protected static $_types = null;

    public static function factory($name) {
        $class = 'df\\aura\\theme\\facet\\'.ucfirst($name);

        if(!class_exists($class)) {
            throw new aura\theme\RuntimeException(
                'Theme facet '.$name.' could not be found'
            );
        }

        return new $class();
    }

    public function renderTo(aura\view\IRenderTarget $target) {
        $view = $target->getView();
        $func = 'renderTo'.$view->getType();
        
        if(method_exists($this, $func)) {
            $this->$func($view);
        }
        
        return $this;
    }
}