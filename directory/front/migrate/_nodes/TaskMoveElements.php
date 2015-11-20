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
use df\mesh;

class TaskMoveElements extends arch\node\Task {

    public function execute() {
        core\dump('This task is deprecated');
        $connection = $this->data->content->element->getUnitAdapter()->getConnection();
        $oldTable = $connection->getTable('nightfire_element');

        if(!$oldTable->exists()) {
            $this->io->writeErrorLine('Original element table does not exist');
            return;
        }

        $this->data->content->element->destroyStorage();
        $this->data->content->element->ensureStorage();
        $newTable = $connection->getTable('content_element');

        $count = 0;
        $this->io->write('Moving rows');

        foreach($oldTable->select() as $row) {
            $newTable->insert($row)->execute();
            $count++;
        }

        $this->io->writeLine(': '.$count.' moved');
        $oldTable->drop();
    }
}