<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\css\processor;

use df;
use df\core;
use df\aura;

abstract class Base implements aura\css\IProcessor {

    public $settings;

    public static function factory($name, $settings=null) {
        if($name instanceof aura\css\IProcessor) {
            if($settings) {
                $name->settings->import($settings);
            }

            return $name;
        }

        $class = 'df\\aura\\css\\processor\\'.ucfirst($name);

        if(!class_exists($class)) {
            throw core\Error::ENotFound(
                'Css processor '.$name.' could not be found'
            );
        }

        return new $class($settings);
    }

    public function __construct($settings=null) {
        $this->settings = core\collection\Tree::factory($settings);
    }
}