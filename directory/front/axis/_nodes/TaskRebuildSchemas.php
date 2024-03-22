<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\axis\_nodes;

use DecodeLabs\Terminus as Cli;
use df\arch;

use df\axis;

class TaskRebuildSchemas extends arch\node\Task
{
    public function execute(): void
    {
        $list = $this->data->axis->schema->select('unitId')
            ->toList('unitId');

        $this->data->axis->schema->delete()->execute();
        $this->data->axis->getSchemaManager()->clearCache();

        foreach ($list as $unitId) {
            try {
                $unit = axis\Model::loadUnitFromId($unitId);
            } catch (axis\Exception $e) {
                Cli::operative('Skipped ' . $unitId . ', definition not found');
                continue;
            }

            Cli::{'yellow'}($unitId . ': ');

            $schema = $unit->buildInitialSchema();
            $unit->updateUnitSchema($schema);
            $unit->validateUnitSchema($schema);
            axis\schema\Manager::getInstance()->store($unit, $schema);

            Cli::success('done');
        }

        $this->data->axis->getSchemaManager()->clearCache();
    }
}
