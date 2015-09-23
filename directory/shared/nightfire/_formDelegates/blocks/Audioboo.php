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
    
class Audioboo extends Base {

    protected function setDefaultValues() {
        $this->values->booId = $this->_block->getBooId();
    }

    public function renderFieldAreaContent(aura\html\widget\FieldArea $fieldArea) {
        $fieldArea->push(
            $this->html->fieldArea($this->_('Audioboom URL or ID'))->push(
                $this->html->textbox($this->fieldName('booId'), $this->values->booId)
                    ->isRequired($this->_isRequired)
            )
        );

        return $this;
    }

    public function apply() {
        $this->data->newValidator()
            ->addRequiredField('booId', 'text')
            ->validate($this->values);

        $this->_block->setBooId($this->values['booId']);

        return $this->_block;
    }
}