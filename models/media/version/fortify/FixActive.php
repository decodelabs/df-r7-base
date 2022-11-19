<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\media\version\fortify;

use df\axis;

class FixActive extends axis\fortify\Base
{
    protected function execute()
    {
        $list = $this->_unit->select('id', 'isActive')
            ->joinRelation('file', 'id as fileId', 'activeVersion')
            ->where('isActive', '=', true)
            ->whereField('activeVersion', '!=', 'version.id')
            ->isUnbuffered(true);

        $count = 0;

        foreach ($list as $row) {
            $count++;
            $this->_unit->update(['isActive' => false])
                ->where('id', '=', $row['id'])
                ->execute();
        }

        yield $count . ' inactive flags cleared';

        $list = $this->_unit->select('id', 'isActive')
            ->joinRelation('file', 'id as fileId', 'activeVersion')
            ->where('isActive', '=', false)
            ->whereField('activeVersion', '=', 'version.id')
            ->isUnbuffered(true);

        $count = 0;

        foreach ($list as $row) {
            $count++;
            $this->_unit->update(['isActive' => true])
                ->where('id', '=', $row['id'])
                ->execute();
        }

        yield ', ' . $count . ' active versions flagged';
    }
}
