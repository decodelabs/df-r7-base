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

class TaskMoveHistory extends arch\task\Action {
    
    public function execute() {
        try {
            $nightfire = $this->data->nightfire;
        } catch(\Exception $e) {
            $this->io->writeErrorLine('Site does not use nightfire');
            return;
        }

        $this->io->write('Moving rows');
        $this->data->content->history->destroyStorage();
        $count = 0;

        foreach($nightfire->history->select() as $row) {
            unset($row['id']);
            $this->data->content->history->insert($row)->execute();
            $count++;
        }

        $this->io->writeLine(': '.$count.' moved');
    }
}