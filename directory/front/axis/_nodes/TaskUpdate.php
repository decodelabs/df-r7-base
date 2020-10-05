<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\axis\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;
use df\axis;
use df\opal;

use DecodeLabs\Terminus as Cli;
use DecodeLabs\Glitch;

class TaskUpdate extends arch\node\Task
{
    protected $_schemaManager;

    public function execute()
    {
        Cli::{'yellow'}('Probing units: ');

        $probe = new axis\introspector\Probe();
        $units = $probe->probeStorageUnits();

        foreach ($units as $key => $inspector) {
            if (!$inspector->canUpdateSchema()) {
                unset($units[$key]);
            }
        }

        $count = count($units);

        Cli::success($count.' updates found');

        if (!$count) {
            return;
        }

        /*
        if (!isset($this->request['noBackup'])) {
            $this->runChild('axis/backup');
            Cli::newLine();
        }

        $this->_schemaManager = axis\schema\Manager::getInstance();

        foreach ($units as $inspector) {
            $this->_update($inspector);
        }

        Cli::newLine();
        axis\schema\Cache::getInstance()->clear();
        */
    }

    protected function _update($inspector)
    {
        Cli::{'.green'}('Updating '.$inspector->getId().' schema from v'.$inspector->getSchemaVersion().' to v'.$inspector->getDefinedSchemaVersion());
        $unit = $inspector->getUnit();

        $schema = $unit->getUnitSchema();
        $unit->updateUnitSchema($schema);
        $unitId = $unit->getUnitId();
        $store = [];

        if ($schema->hasPrimaryIndexChanged()) {
            foreach ($this->_schemaManager->fetchStoredUnitList() as $relationUnitId) {
                $relationUnit = axis\Model::loadUnitFromId($relationUnitId);
                $relationSchema = $relationUnit->getUnitSchema();
                $update = false;

                foreach ($relationSchema->getFields() as $relationField) {
                    if (!$relationField instanceof axis\schema\IRelationField
                    || $relationField instanceof opal\schema\INullPrimitiveField
                    || $relationField->getTargetUnitId() != $unitId) {
                        continue;
                    }

                    if ($relationField instanceof opal\schema\IOneRelationField) {
                        $relationField->markAsChanged();
                        $relationSchema->replacePreparedField($relationField);
                        $update = true;
                    } else {
                        Glitch::incomplete([$relationField, $relationUnit]);
                    }
                }

                if ($update) {
                    Cli::{'.green'}('Updating '.$inspector->getId().' relation field on '.$relationUnit->getUnitId());

                    $relationSchema->sanitize($relationUnit);

                    if ($relationUnit->storageExists()) {
                        $relationUnit->updateStorageFromSchema($relationSchema);
                    }

                    $store[$relationUnit->getUnitId()] = [
                        'unit' => $relationUnit,
                        'schema' => $relationSchema
                    ];
                }
            }
        }

        if ($unit->storageExists()) {
            $unit->updateStorageFromSchema($schema);
        }

        $store[$unit->getUnitId()] = [
            'unit' => $unit,
            'schema' => $schema
        ];

        foreach ($store as $unitId => $set) {
            $this->_schemaManager->store($set['unit'], $set['schema']);
        }
    }
}
