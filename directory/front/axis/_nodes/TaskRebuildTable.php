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

class TaskRebuildTable extends arch\node\Task
{
    public function extractCliArguments(core\cli\ICommand $command)
    {
        $hasUnit = false;

        foreach ($command->getArguments() as $arg) {
            if (!$arg->isOption()) {
                if (!$hasUnit) {
                    $this->request->query->unit = (string)$arg;
                    $hasUnit = true;
                }
            } elseif ($arg->getOption() === '-d') {
                $this->request->query->delete = true;
            }
        }
    }

    public function execute()
    {
        $unitId = $this->request['unit'];

        if (!$unit = axis\Model::loadUnitFromId($unitId)) {
            throw core\Error::{'axis/unit/ENotFound'}(
                'Unit '.$unitId.' not found'
            );
        }

        if ($unit->getUnitType() != 'table') {
            throw core\Error::{'axis/unit/EDomain'}(
                'Unit '.$unitId.' is not a table'
            );
        }

        if (!$unit instanceof axis\ISchemaBasedStorageUnit) {
            throw Glitch::{'df/axis/unit/EDomain'}(
                'Unit '.$unitId.' is not schemas based'
            );
        }

        $this->io->writeLine('Rebuilding unit '.$unit->getUnitId());
        $adapter = $unit->getUnitAdapter();

        $parts = explode('\\', get_class($adapter));
        $adapterName = array_pop($parts);

        $func = '_rebuild'.$adapterName.'Table';

        if (!method_exists($this, $func)) {
            throw Glitch::{'df/axis/unit/EDomain'}(
                'Table unit '.$unitId.' is using an adapter that doesn\'t currently support rebuilding'
            );
        }

        $schema = $unit->buildInitialSchema();
        $unit->updateUnitSchema($schema);
        $unit->validateUnitSchema($schema);

        $this->{$func}($unit, $schema);

        $this->io->writeLine();
        $this->io->writeLine('Updating schema cache');

        axis\schema\Cache::getInstance()->clearAll();
        axis\schema\Manager::getInstance()->store($unit, $schema);

        gc_collect_cycles();
        $this->io->writeLine('Done');
    }

    protected function _rebuildRdbmsTable(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $axisSchema)
    {
        $this->io->writeLine('Switching to rdbms mode');

        $adapter = $unit->getUnitAdapter();
        $connection = $adapter->getConnection();
        $newConnection = clone $connection;
        $currentTable = clone $adapter->getQuerySourceAdapter();

        if (!$currentTable->exists()) {
            $this->io->writeLine('Unit rdbms table '.$currentTable->getName().' not found - nothing to do');
            return;
        }

        $translator = new axis\schema\translator\Rdbms($unit, $connection, $axisSchema);
        $dbSchema = $translator->createFreshTargetSchema();
        $currentTableName = $dbSchema->getName();
        $dbSchema->setName($currentTableName.'__rebuild__');

        try {
            $this->io->writeLine('Building copy table');
            $newTable = $newConnection->createTable($dbSchema);
        } catch (opal\rdbms\TableConflictException $e) {
            throw core\Error::{'axis/unit/ERuntime'}(
                'Table unit '.$unit->getUnitId().' is currently rebuilding in another process'
            );
        }

        $this->io->writeLine();
        $this->io->writeLine('Copying data');
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

            if (!($count % 1000)) {
                $this->io->write('.');
            }
        }

        $insert->execute();
        $this->io->writeLine('.');
        $this->io->writeLine('Copied '.$count.' rows');

        $this->io->writeLine('Renaming tables');
        $currentTable->rename($currentTableName.axis\IUnitOptions::BACKUP_SUFFIX.$this->format->customDate('now', 'Ymd_his'));

        $newTable->rename($currentTableName);


        if ($this->request->query['delete'] === true) {
            $this->io->writeLine('Deleting backup: '.$currentTable->getName());
            $currentTable->drop();
        }
    }
}
