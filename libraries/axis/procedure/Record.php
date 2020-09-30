<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\procedure;

use df;
use df\core;
use df\axis;
use df\opal;

use DecodeLabs\Exceptional;

abstract class Record extends Base implements IRecordProcedure
{
    const CAN_CREATE = false;

    public $record;

    public function __construct(axis\IUnit $unit, $values, $record=null)
    {
        parent::__construct($unit, $values);

        if ($record instanceof opal\record\IRecord) {
            $this->setRecord($record);
        }
    }

    public function setRecord(opal\record\IRecord $record=null)
    {
        $this->record = $record;
        return $this;
    }

    public function getRecord()
    {
        if (!$this->record) {
            $this->record = $this->_getRecord();
        }

        return $this->record;
    }

    protected function _getRecord()
    {
        return null;
    }

    public function prepare()
    {
        $this->getRecord();

        if (static::CAN_CREATE && !$this->record && $this->_unit instanceof opal\query\IAdapter) {
            $this->record = $this->_unit->newRecord();
        }

        if (!$this->record) {
            throw Exceptional::Runtime(
                'Record procedure has no record to operate on'
            );
        }

        $this->_prepareValidator();
        $this->_prepare();

        $this->_isPrepared = true;
        return $this;
    }

    protected function _prepareValidator()
    {
        $this->_unit->prepareValidator($this->validator, $this->record);
    }
}
