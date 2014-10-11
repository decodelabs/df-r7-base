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
use df\opal;

class TaskUpdate extends arch\task\Action {
    
    protected $_schemaDefinition;
    protected $_clusterUnit;

    public function execute() {
        $this->io->write('Probing units...');

        $probe = new axis\introspector\Probe();
        $units = $probe->probeStorageUnits();

        foreach($units as $key => $inspector) {
            if(!$inspector->canUpdateSchema()) {
                unset($units[$key]);
            }
        }

        $count = count($units);

        $this->io->writeLine(' found '.$count.' to update');

        if(!$count) {
            return;
        }

        if(!isset($this->request->query->noBackup)) {
            $this->io->writeLine('Creating full backup...');
            $this->io->writeLine();
            $this->runChild('axis/backup');
            $this->io->writeLine();
        }

        $this->_schemaDefinition = axis\Model::getSchemaDefinitionUnit();

        try {
            $this->_clusterUnit = axis\Model::loadClusterUnit();
        } catch(axis\RuntimeException $e) {
            $this->_clusterUnit = null;
        }

        foreach($units as $inspector) {
            $this->_update($inspector);
        }

        $this->io->writeLine();
        $this->io->writeLine('Clearing schema chache');
        axis\schema\Cache::getInstance()->clear();
    }

    protected function _update($inspector) {
        $this->io->writeLine('Updating '.$inspector->getId().' schema from v'.$inspector->getSchemaVersion().' to v'.$inspector->getDefinedSchemaVersion());
        $unit = $inspector->getUnit();

        if($unit->getClusterId()) {
            $unit = axis\Model::loadUnitFromId($unit->getGlobalUnitId());
        }

        $schema = $unit->getUnitSchema();
        $unit->updateUnitSchema($schema);
        $unitId = $unit->getGlobalUnitId();
        $store = [];

        if($schema->hasPrimaryIndexChanged()) {
            foreach($this->_schemaDefinition->fetchStoredUnitList() as $relationUnitId) {
                $relationUnit = axis\Model::loadUnitFromId($relationUnitId);
                $relationSchema = $relationUnit->getUnitSchema();
                $update = false;

                foreach($relationSchema->getFields() as $relationField) {
                    if(!$relationField instanceof axis\schema\IRelationField
                    || $relationField instanceof opal\schema\INullPrimitiveField
                    || $relationField->getTargetUnitId() != $unitId) {
                        continue;
                    }

                    if($relationField instanceof opal\schema\IOneRelationField) {
                        $relationField->markAsChanged();
                        $relationSchema->replacePreparedField($relationField);
                        $update = true;
                    } else {
                        core\stub($relationField, $relationUnit);
                    }
                }

                if($update) {
                    $this->io->writeLine('Updating '.$inspector->getId().' relation field on '.$relationUnit->getUnitId());

                    $relationSchema->sanitize($relationUnit);

                    if($relationUnit->storageExists()) {
                        $relationUnit->updateStorageFromSchema($relationSchema);
                    }

                    $store[$relationUnit->getUnitId()] = [
                        'unit' => $relationUnit,
                        'schema' => $relationSchema
                    ];
                }
            }
        }

        if($unit->storageExists()) {
            $unit->updateStorageFromSchema($schema);
        }

        $store[$unit->getUnitId()] = [
            'unit' => $unit,
            'schema' => $schema
        ];

        if($this->_clusterUnit) {
            foreach($this->_clusterUnit->select('@primary as primary') as $row) {
                $clusterId = $row['primary'];

                foreach($store as $unitId => $set) {
                    $clusterUnit = axis\Model::loadUnitFromId($unitId, $clusterId);

                    if($clusterUnit->storageExists()) {
                        $this->io->writeLine('Updating '.$inspector->getId().' on cluster '.$clusterId);
                        $clusterUnit->updateStorageFromSchema($set['schema']);
                    }
                }
            }
        }


        foreach($store as $unitId => $set) {
            $this->_schemaDefinition->store($set['unit'], $set['schema']);
        }
    }
}