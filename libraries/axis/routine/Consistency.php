<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\routine;

use df;
use df\core;
use df\axis;

class Consistency extends Base implements IConsistencyRoutine {
    
    public static function loadAll(axis\IUnit $unit) {
        $output = [];
        $path = 'apex/models/'.$unit->getModel()->getModelName().'/'.$unit->getUnitName().'/routines';

        foreach(df\Launchpad::$loader->lookupClassList($path, false) as $name => $class) {
            if(!class_exists($class)) {
                continue;
            }

            $ref = new \ReflectionClass($class);

            if($ref->isAbstract() || !$ref->implementsInterface('df\\axis\\routine\\IConsistencyRoutine')) {
                continue;
            }

            $output[$name] = new $class($unit);
        }

        return $output;
    }

    public function canExecute() {
        return true;
    }
}