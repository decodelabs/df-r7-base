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

class TaskRebuildSchemas extends arch\node\Task {

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
        }

        axis\schema\Cache::getInstance()->clearAll();

        foreach($list as $unitId) {
            $schema = $unit->buildInitialSchema();
            $unit->updateUnitSchema($schema);
            $unit->validateUnitSchema($schema);
            axis\schema\Manager::getInstance()->store($unit, $schema);

            $this->io->writeLine('Updated '.$unitId);
        }
    }
}