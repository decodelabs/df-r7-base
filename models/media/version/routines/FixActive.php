<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\media\version\routines;

use df;
use df\core;
use df\apex;
use df\axis;

class FixActive extends axis\routine\Consistency {
    
    protected function _execute() {
        $this->io->write('Clearing inactive version flags...');

        $list = $this->_unit->select('id', 'isActive')
            ->joinRelation('file', 'id as fileId', 'activeVersion')
            ->where('isActive', '=', true)
            ->whereField('activeVersion', '!=', 'version.id')
            ->isUnbuffered(true);

        $count = 0;

        foreach($list as $row) {
            $count++;
            $this->_unit->update(['isActive' => false])
                ->where('id', '=', $row['id'])
                ->execute();
        }

        $this->io->writeLine(' '.$count.' updated');

        $this->io->write('Ensuring active flagged...');

        $list = $this->_unit->select('id', 'isActive')
            ->joinRelation('file', 'id as fileId', 'activeVersion')
            ->where('isActive', '=', false)
            ->whereField('activeVersion', '=', 'version.id')
            ->isUnbuffered(true);

        $count = 0;

        foreach($list as $row) {
            $count++;
            $this->_unit->update(['isActive' => true])
                ->where('id', '=', $row['id'])
                ->execute();
        }

        $this->io->writeLine(' '.$count.' updated');
    }
}