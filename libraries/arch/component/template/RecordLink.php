<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\component\template;

use df;
use df\core;
use df\arch;
use df\aura;
    
abstract class RecordLink extends arch\component\Base {

    const DEFAULT_MISSING_MESSAGE = 'not found';

    protected $_icon = 'item';
    protected $_disposition = 'informative';
    protected $_isNullable = false;
    protected $_note;
    protected $_missingMessage;
    protected $_record;

    protected function _init($record=null) {
        if($record) {
            $this->setRecord($record);
        }
    }

// Record
    public function setRecord($record) {
        if(is_scalar($record)) {
            $record = ['id' => $record];
        }

        $this->_record = $record;
        return $this;
    }

    public function getRecord() {
        return $this->_record;
    }

// Icon
    public function setIcon($icon) {
        $this->_icon = $icon;
        return $this;
    }

    public function getIcon() {
        return $this->_icon;
    }

// Disposition
    public function setDisposition($disposition) {
        $this->_disposition = $disposition;
        return $this;
    }

    public function getDisposition() {
        return $this->_disposition;
    }

// Nullable
    public function isNullable($flag=null) {
        if($flag !== null) {
            $this->_isNullable = (bool)$flag;
            return $this;
        }

        return $this->_isNullable;
    }

// Note
    public function setNote($note) {
        $this->_note = $note;
        return $this;
    }

    public function getNote() {
        return $this->_note;
    }

// Missing message
    public function setMissingMessage($message) {
        $this->_missingMessage = $message;
        return $this;
    }

    public function getMissingMessage() {
        if(empty($this->_missingMessage)) {
            return static::DEFAULT_MISSING_MESSAGE;
        }

        return $this->_missingMessage;
    }

// Render
    protected function _execute() {
        if($this->_record === null && $this->_isNullable) {
            return null;
        }

        $id = null;

        if($this->_record) {
            $id = $this->_getRecordId();
        }

        if(!$this->_record || $id === null) {
            $message = $this->_missingMessage;

            if(empty($message)) {
                $message = $this->_(static::DEFAULT_MISSING_MESSAGE);
            }

            return $this->getView()->html->link('#', $message)
                ->isDisabled(true)
                ->setIcon('error')
                ->addClass('state-error');
        }

        $name = $this->_getRecordName();
        $url = $this->_getRecordUrl($id);

        return $this->getView()->html->link($url, $name)
            ->setIcon($this->_icon)
            ->setDisposition($this->_disposition)
            ->setNote($this->_note);
    }

    protected function _getRecordId() {
        return $this->_record['id'];
    }

    protected function _getRecordName() {
        return $this->_record['name'];
    }

    abstract protected function _getRecordUrl($id);
}