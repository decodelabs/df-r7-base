<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\axis\_nodes;

use DecodeLabs\Dictum;
use DecodeLabs\Exceptional;
use DecodeLabs\Terminus as Cli;

use df\arch;
use df\axis;
use df\opal;

class TaskRebuildTable extends arch\node\Task
{
    public function prepareArguments(): array
    {
        return Cli::$command
            ->addArgument('?unit', 'Unit to purge')
            ->addArgument('-delete|d', 'Delete backup')
            ->toArray();
    }

    public function execute(): void
    {
        $unitId = $this->request['unit'];

        if (!$unit = axis\Model::loadUnitFromId($unitId)) {
            throw Exceptional::{'df/axis/unit/NotFound'}(
                'Unit ' . $unitId . ' not found'
            );
        }

        if ($unit->getUnitType() != 'table') {
            throw Exceptional::{'df/axis/unit/Domain'}(
                'Unit ' . $unitId . ' is not a table'
            );
        }

        if (!$unit instanceof axis\ISchemaBasedStorageUnit) {
            throw Exceptional::{'df/axis/unit/Domain'}(
                'Unit ' . $unitId . ' is not schemas based'
            );
        }

        Cli::info('Rebuilding unit ' . $unit->getUnitId());
        $adapter = $unit->getUnitAdapter();

        $parts = explode('\\', get_class($adapter));
        $adapterName = array_pop($parts);

        $func = '_rebuild' . $adapterName . 'Table';

        if (!method_exists($this, $func)) {
            throw Exceptional::{'df/axis/unit/Domain'}(
                'Table unit ' . $unitId . ' is using an adapter that doesn\'t currently support rebuilding'
            );
        }

        $schema = $unit->buildInitialSchema();
        $unit->updateUnitSchema($schema);
        $unit->validateUnitSchema($schema);

        $this->{$func}($unit, $schema);

        axis\schema\Manager::getInstance()->clearCache();
        axis\schema\Manager::getInstance()->store($unit, $schema);

        gc_collect_cycles();
    }

    protected function _rebuildRdbmsTable(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $axisSchema)
    {
        Cli::info('Switching to rdbms mode');

        $adapter = $unit->getUnitAdapter();
        $connection = $adapter->getConnection();
        $newConnection = clone $connection;
        $currentTable = clone $adapter->getQuerySourceAdapter();

        if (!$currentTable->exists()) {
            Cli::info('Unit rdbms table ' . $currentTable->getName() . ' not found - nothing to do');
            return;
        }

        $translator = new axis\schema\translator\Rdbms($unit, $connection, $axisSchema);
        $dbSchema = $translator->createFreshTargetSchema();
        $currentTableName = $dbSchema->getName();
        $dbSchema->setName($currentTableName . '__rebuild__');

        try {
            $newTable = $newConnection->createTable($dbSchema);
        } catch (opal\rdbms\TableConflictException $e) {
            throw Exceptional::{'df/axis/unit/Runtime'}(
                'Table unit ' . $unit->getUnitId() . ' is currently rebuilding in another process'
            );
        }

        $total = $currentTable->select()->count();

        if ($total > 0) {
            Cli::newLine();
            $progressBar = Cli::newProgressBar(0, $total);

            $insert = $newTable->batchInsert();
            $count = 0;

            $fields = $dbSchema->getFields();
            $currentFields = $currentTable->getSchema()->getFields();
            $generatorFields = [];
            $nonNullFields = [];
            $newFields = [];

            foreach ($fields as $fieldName => $field) {
                if (isset($currentFields[$fieldName])) {
                    continue;
                }

                $axisField = $axisSchema->getField($fieldName);

                if (!$field->isNullable()) {
                    $nonNullFields[$fieldName] = $field;
                }

                if ($axisField instanceof opal\schema\IAutoGeneratorField) {
                    $generatorFields[$fieldName] = $axisField;
                } else {
                    $newFields[$fieldName] = $field;
                }
            }

            $query = $currentTable->select()
                ->isUnbuffered(true);

            if (isset($currentFields['creationDate'])) {
                $query->orderBy('creationDate ASC');
            }

            foreach ($query as $row) {
                foreach ($row as $key => $value) {
                    if (!isset($fields[$key])) {
                        unset($row[$key]);
                    }

                    if ($value === null && isset($nonNullFields[$key])) {
                        $row[$key] = $value = $nonNullFields[$key]->getDefaultNonNullValue();
                    }
                }

                foreach ($generatorFields as $fieldName => $axisField) {
                    if ($axisField instanceof opal\query\IFieldValueProcessor) {
                        $row[$fieldName] = $axisField->generateInsertValue($row);
                    }
                }

                foreach ($newFields as $fieldName => $newField) {
                    if ($newField->isNullable()) {
                        $row[$fieldName] = null;
                    } else {
                        $row[$fieldName] = $newField->getDefaultNonNullValue();
                    }
                }

                $insert->addRow($row);
                $count++;
                $progressBar->advance($count);
            }

            $insert->execute();
            $progressBar->complete();
        }

        Cli::newLine();
        Cli::{'yellow'}('Renaming tables: ');
        $currentTable->rename($currentTableName . axis\IUnitOptions::BACKUP_SUFFIX . Dictum::$time->format('now', 'Ymd_his', 'UTC'));
        $newTable->rename($currentTableName);
        Cli::success('done');


        if ($this->request->query['delete'] === true) {
            Cli::{'yellow'}('Deleting backup: ' . $currentTable->getName() . ': ');
            $currentTable->drop();
            Cli::success('done');
        }
    }
}
