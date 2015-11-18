<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\shared\nightfire\_formDelegates\blocks;

use df;
use df\core;
use df\apex;
use df\arch;
use df\aura;
use df\fire;

class Error extends Base {

    protected function setDefaultValues() {
        $this->setStore('type', $this->_block->getType());
        $this->setStore('data', $this->_block->getData());

        if($error = $this->_block->getError()) {
            $this->setStore('message', $error->getMessage());
        }
    }

    protected function afterInit() {
        $this->_block->setType($this->getStore('type'));
        $this->_block->setData($this->getStore('data'));
    }

    public function renderFieldContent(aura\html\widget\Field $field) {
        $output = $this->html->flashMessage($this->_(
            'Error loading block type: '.$this->getStore('type')
        ), 'error');

        $output->setDescription($this->getStore('message'));
        $this->_block->setData($this->getStore('data'));

        $field->push($output);

        return $this;
    }

    public function apply() {
        $this->values->addError('noentry', 'Must update block!');
    }
}