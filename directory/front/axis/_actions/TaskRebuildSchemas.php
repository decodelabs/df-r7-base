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

class TaskRebuildSchemas extends arch\task\Action {

    public function execute() {
        $list = $this->data->axis->schema->select('unitId')
            ->toList('unitId');

        foreach($list as $unitId) {
            try {
                $unit = axis\Model::loadUnitFromId($unitId);
            } catch(axis\IException $e) {
                $this->io->writeLine('Skipped '.$unitId.', definition not found');

                $this->data->axis->schema->delete()
                    ->where('unitId', '=', $unitId)
                    ->execute();

                continue;
            }

            $schema = $unit->buildInitialSchema();
            $unit->updateUnitSchema($schema);
            $unit->validateUnitSchema($schema);
            axis\schema\Manager::getInstance()->store($unit, $schema);

            $this->io->writeLine('Updated '.$unitId);
        }

        axis\schema\Cache::getInstance()->clearAll();
    }
}