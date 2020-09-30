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

use DecodeLabs\Terminus\Cli;

class TaskRebuildSchemas extends arch\node\Task
{
    public function execute()
    {
        $list = $this->data->axis->schema->select('unitId')
            ->toList('unitId');

        $this->data->axis->schema->delete()->execute();
        axis\schema\Cache::getInstance()->clearAll();

        foreach ($list as $unitId) {
            try {
                $unit = axis\Model::loadUnitFromId($unitId);
            } catch (axis\Exception $e) {
                Cli::operative('Skipped '.$unitId.', definition not found');
                continue;
            }

            Cli::{'yellow'}($unitId.': ');

            $schema = $unit->buildInitialSchema();
            $unit->updateUnitSchema($schema);
            $unit->validateUnitSchema($schema);
            axis\schema\Manager::getInstance()->store($unit, $schema);

            Cli::success('done');
        }

        axis\schema\Cache::getInstance()->clearAll();
    }
}
