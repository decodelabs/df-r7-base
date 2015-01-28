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

class TaskInit extends arch\task\Action {
    
    public function execute() {
        $this->io->write('Looking up config classes in:');
        $libList = df\Launchpad::$loader->lookupLibraryList();
        $classes = [];

        foreach($libList as $libName) {
            $this->io->write(' '.$libName);
            $classes = array_merge($classes, $this->data->config->findIn($libName));
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

            $this->io->writeLine('Init '.ucfirst($config->getConfigId()));
        }
    }
}