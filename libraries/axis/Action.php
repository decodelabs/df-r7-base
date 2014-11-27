<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis;

use df;
use df\core;
use df\axis;
use df\opal;

class Action implements IAction {
    
    public $validator;
    public $record;
    public $unit;
    public $values;
    public $args = [];

    protected $_isPrepared = false;

    public function __construct(IUnit $unit, $values, $args=null, array $dataMap=null) {
        $record = null;

        if($args instanceof opal\record\IRecord) {
            $record = $args;
        } else if(is_array($args)) {
            $this->args = $args;

            if(isset($this->args['record'])) {
                if($this->args['record'] instanceof opal\record\IRecord) {
                    $record = $this->args['record'];
                }

                unset($this->args['record']);
            }
        }

        if($record) {
            if(!$record instanceof opal\record\IRecord) {
                throw new RuntimeException('Invalid record passed to action');
            }

            if(!$record->getRecordAdapter() instanceof $unit) {
                throw new RuntimeException('Record is not of type '.$unit->getUnitId());
            }
        }

        $this->unit = $unit;
        $this->record = $record;
        $this->validator = new core\validate\Handler();

        if(!$values instanceof core\collection\IInputTree) {
            $values = core\collection\InputTree::factory($values);
        }

        $this->values = $values;
        $this->validator->setDataMap($dataMap);
    }

    public function prepare() {
        if(!$this->record && $this->unit instanceof opal\query\IAdapter) {
            $this->record = $this->unit->newRecord();
        }

        $this->unit->prepareValidator($this->validator, $this->record);
        $this->_isPrepared = true;
        return $this;
    }

    public function validate() {
        if(!$this->_isPrepared) {
            $this->prepare();
        }

        $this->validator->validate($this->values);
        return $this;
    }

    public function isValid() {
        return $this->values->isValid() && $this->validator->isValid();
    }
}