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
    
class Heading extends Base {

    protected function setDefaultValues() {
        $this->values->heading = $this->_block->getHeading();
        $this->values->level = $this->_block->getHeadingLevel();
    }

    public function renderFieldAreaContent(aura\html\widget\FieldArea $fieldArea) {
        $fieldArea->push(
            $this->html->fieldArea($this->_('Heading text'))->push(
                $this->html->selectList($this->fieldName('level'), $this->values->level, [
                        1 => 'h1',
                        2 => 'h2',
                        3 => 'h3',
                        4 => 'h4',
                        5 => 'h5',
                        6 => 'h6'
                    ]),


                $this->html->textbox($this->fieldName('heading'), $this->values->heading)
                    ->isRequired($this->_isRequired)
            )
        );

        return $this;
    }

    public function apply() {
        $this->data->newValidator()
            ->addRequiredField('heading', 'text')
            ->addRequiredField('level', 'integer')
                ->setRange(1, 6)
            ->validate($this->values);

        $this->_block->setHeading($this->values['heading']);
        $this->_block->setHeadingLevel($this->values['level']);

        return $this->_block;
    }
}