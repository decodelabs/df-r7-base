<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\migrate\_actions;

use df;
use df\core;
use df\apex;
use df\arch;
use df\axis;
use df\mesh;

class TaskMoveHistory extends arch\task\Action {
    
    public function execute() {
        try {
            $nightfire = $this->data->nightfire;
        } catch(\Exception $e) {
            $this->io->writeErrorLine('Site does not use nightfire');
            return;
        }

        $connection = $nightfire->element->getUnitAdapter()->getConnection();
        $table = $connection->getTable('nightfire_history');

        if(!$table->exists()) {
            $this->io->writeErrorLine('Original history table does not exist');
            return;
        }

        $this->data->content->history->destroyStorage();
        $count = 0;

        $this->io->write('Moving rows');

        foreach($table->select() as $row) {
            $locator = new mesh\entity\Locator($row['entity_domain']);
            $locator->setId($row['entity_id']);
            unset($row['id'], $row['entity_domain'], $row['entity_id']);
            $row['entity'] = $locator;
            $this->data->content->history->insert($row)->execute();
            $count++;
        }

        $this->io->writeLine(': '.$count.' moved');
        $table->drop();
    }
}