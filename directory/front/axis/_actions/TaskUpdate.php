<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\axis\_actions;

use df;
use df\core;
use df\apex;
use df\arch;
use df\axis;

class TaskUpdate extends arch\task\Action {
    
    public function execute() {
        $this->response->write('Probing units...');

        $probe = new axis\introspector\Probe();
        $units = $probe->probeStorageUnits();

        foreach($units as $key => $inspector) {
            if(!$inspector->canUpdateSchema()) {
                unset($units[$key]);
            }
        }

        $count = count($units);

        $this->response->writeLine(' found '.$count.' to update');

        if(!$count) {
            return;
        }

        if(!isset($this->request->query->noBackup)) {
            $this->response->writeLine('Creating full backup...');
            $this->response->writeLine();
            $this->runChild('axis/backup');
            $this->response->writeLine();
        }

        $schemaDefinition = axis\Model::getSchemaDefinitionUnit();

        foreach($units as $inspector) {
            $this->response->writeLine('Updating '.$inspector->getId().' schema from v'.$inspector->getSchemaVersion().' to v'.$inspector->getDefinedSchemaVersion());
            $schemaDefinition->update($inspector->getUnit());
        }

        $this->response->writeLine('Clearing schema chache');
        axis\schema\Cache::getInstance()->clear();
    }
}