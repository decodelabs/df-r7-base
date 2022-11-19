<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\models\config;

use DecodeLabs\R7\Legacy;

use df\axis;

class Model extends axis\Model
{
    public function findIn($path)
    {
        $output = [];

        foreach (Legacy::getLoader()->lookupClassList($path, true) as $name => $class) {
            if (!class_exists($class)) {
                continue;
            }


            $ref = new \ReflectionClass($class);

            if ($ref->implementsInterface('df\\core\\IConfig')) {
                $output[$class] = $ref->implementsInterface('df\\axis\\IUnit');
            }
        }

        foreach (Legacy::getLoader()->lookupFolderList($path) as $dirName => $dirPath) {
            if ($path == 'apex' && !in_array($dirName, ['libraries', 'directory', 'models', 'themes'])) {
                continue;
            }

            $output = array_merge($output, $this->findIn($path . '/' . $dirName));
        }

        return $output;
    }
}
