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
use df\mesh;

class TaskMoveComments extends arch\task\Action {
    
    public function execute() {
        $connection = $this->data->content->comment->getUnitAdapter()->getConnection();
        $table = $connection->getTable('interact_comment');

        if(!$table->exists()) {
            $this->io->writeErrorLine('Original comment table does not exist');
            return;
        }

        $this->data->content->comment->destroyStorage();
        $count = 0;
        $this->io->write('Moving rows');

        foreach($table->select() as $row) {
            $locator = new mesh\entity\Locator($row['topic_domain']);
            $locator->setId($row['topic_id']);
            $row['owner'] = $row['owner_id'];
            $row['root'] = $row['root_id'];
            $row['inReplyTo'] = $row['inReplyTo_id'];
            unset($row['id'], $row['topic_domain'], $row['topic_id'], $row['owner_id'], $row['root_id'], $row['inReplyTo_id']);
            $row['topic'] = $locator;
            $this->data->content->comment->insert($row)->execute();
            $count++;
        }

        $this->io->writeLine(': '.$count.' moved');
        $table->drop();
    }
}