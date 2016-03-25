<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\migrate\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;

class TaskConvertSessions extends arch\node\Task {

    public function execute() {
        $this->io->writeLine('Upgrading sessions');
        $model = $this->data->session;
        $connection = $model->descriptor->getUnitAdapter()->getConnection();

        $manifestTable = $connection->getTable('session_manifest');
        $dataTable = $connection->getTable('session_data');
        $descriptorUnit = $model->descriptor;
        $nodeUnit = $model->node;

        $descriptorUnit->select()->toArray();
        $nodeUnit->select()->toArray();
        $connection->getTable('session_descriptor')->truncate();
        $connection->getTable('session_node')->truncate();

        $this->io->write('Copying manifest data...');
        $i = 0;

        foreach($manifestTable->select() as $row) {
            $descriptorUnit->insert([
                    'id' => $row['internalId'],
                    'publicKey' => $row['externalId'],
                    'transitionKey' => $row['transitionId'],
                    'user' => $row['userId'],
                    'startTime' => $row['startTime'],
                    'transitionTime' => $row['transitionTime'],
                    'accessTime' => $row['accessTime']
                ])
                ->execute();

            $i++;
        }

        $this->io->writeLine(' '.$i.' rows processed');


        $this->io->write('Copying node data...');
        $i = 0;

        foreach($dataTable->select() as $row) {
            $nodeUnit->insert([
                    'bucket' => $row['namespace'],
                    'key' => $row['key'],
                    'descriptor' => $row['internalId'],
                    'value' => $row['value'],
                    'creationTime' => $row['creationTime'],
                    'updateTime' => $row['updateTime']
                ])
                ->execute();

            $i++;
        }

        $this->io->writeLine(' '.$i.' rows processed');

        $this->runChild('application/build');

        $this->io->write('Dropping session tables...');
        $manifestTable->drop();
        $dataTable->drop();
        $this->io->writeLine(' done');

        $this->data->axis->schema->delete()
            ->where('unitId', 'in', ['session/manifest', 'session/data'])
            ->execute();
    }
}