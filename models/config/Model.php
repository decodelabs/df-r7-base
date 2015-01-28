<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\config;

use df;
use df\core;
use df\apex;
use df\axis;

class Model extends axis\Model {
    
    public function findIn($path) {
        $classes = df\Launchpad::$loader->lookupClassList($path, ['php']);
        $output = [];

        foreach($classes as $name => $class) {
            if(!class_exists($class)) {
                continue;
            }

            $ref = new \ReflectionClass($class);

            if($ref->implementsInterface('df\\core\\IConfig')) {
                $output[$class] = $ref->implementsInterface('df\\axis\\IUnit');
            }
        }

        foreach(df\Launchpad::$loader->lookupFolderList($path) as $dirName => $dirPath) {
            $output = array_merge($output, $this->findIn($path.'/'.$dirName));
        }

        return $output;
    }
}