<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\content\element;

use df;
use df\core;
use df\apex;
use df\opal;
use df\axis;

class Record extends opal\record\Base {

    protected function onPreSave($queue, $job) {
        $this->_writeHistory($queue, $job);
    }

    protected function _writeHistory($queue, $job) {
        $isNew = $this->isNew();

        if(!$isNew && !$this->hasChanged()) {
            return $this;
        }

        if($isNew) {
            $description = 'Created element '.$this['name'];
        } else {
            $lines = [];

            foreach($this->getChangedValues() as $field => $value) {
                switch($field) {
                    case 'slug':
                        $lines[] = 'Set slug to '.$value;
                        break;

                    case 'name':
                        $lines[] = 'Renamed to "'.$value.'"';
                        break;

                    case 'body':
                        $lines[] = 'Updated body content';
                        break;
                }
            }

            if(empty($lines)) {
                return $this;
            }

            $description = implode("\n", $lines);
        }

        $this->getAdapter()->context->data->content->history->createRecordEntry(
            $this, $queue, $job, $description
        );
    }

    public function getSlotDefinition() {
        return $this->getAdapter()
            ->getUnitSchema()
            ->getField('body')
            ->getSlotDefinition();
    }
}