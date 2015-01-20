<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\config\_actions;

use df;
use df\core;
use df\apex;
use df\arch;
use df\axis;

class TaskTidy extends arch\task\Action {
    
    public function execute() {
        $this->throwError(500, 'This task doesn\'t work yet!');

        $this->io->write('Looking up config classes in:');
        $libList = ['apex'];
        $classes = [];

        foreach(df\Launchpad::$loader->lookupFolderList('/') as $folder) {
            $libList[] = basename($folder);
        }

        $libList = array_unique($libList);
        sort($libList);

        foreach($libList as $libName) {
            $this->io->write(' '.$libName);
            $classes = array_merge($classes, $this->_walkLibrary($libName));
        }

        $classCount = count($classes);
        $this->io->writeLine("\n".'Found '.$classCount.' total');

        if(!$classCount) {
            return;
        }


        foreach($classes as $class => $isUnit) {
            if($isUnit) {
                $id = implode('/', array_slice(explode('\\', $class), -3, -1));
                $config = axis\Model::loadUnitFromId($id);
            } else {
                $config = $class::getInstance();
            }

            $this->io->writeLine('Processing '.ucfirst($config->getConfigId()));
            $config->tidyConfigValues();
        }
    }

    protected function _walkLibrary($path) {
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
            $output = array_merge($output, $this->_walkLibrary($path.'/'.$dirName));
        }

        return $output;
    }
}