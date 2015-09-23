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

class LibraryImage extends Base {

    protected function loadDelegates() {
        $this->loadDelegate('image', '~/media/FileSelector')
            ->setAcceptTypes('image/*')
            ->isForOne(true)
            ->isRequired(true);
    }

    protected function setDefaultValues() {
        $this->getDelegate('image')->setSelected($this->_block->getImageId());

        if($this->_block->shouldStoreDimensions()) {
            $this->values->width = $this->_block->getWidth();
            $this->values->height = $this->_block->getHeight();
        }
    }

    public function renderFieldAreaContent(aura\html\widget\FieldArea $fieldArea) {
        $fieldArea->addFieldArea($this->_('Library image'))->push(
            $this->getDelegate('image')
        );

        if($this->_block->shouldStoreDimensions()) {
            $fieldArea->push(
                $this->html->fieldArea($this->_('Dimensions (optional)'))->push(
                    $this->html->numberTextbox($this->fieldName('width'), $this->values->width)
                        ->setRange(1, null, 1),

                    ' x ',

                    $this->html->numberTextbox($this->fieldName('height'), $this->values->height)
                        ->setRange(1, null, 1)
                )
            );
        }

        return $this;
    }

    public function apply() {
        $validator = $this->data->newValidator()
            // Image
            ->addField('image', 'delegate')
                ->fromForm($this)

            ->chainIf($this->_block->shouldStoreDimensions(), function($validator) {
                $validator
                    // Width
                    ->addField('width', 'integer')
                        ->setMin(1)

                    // Height
                    ->addField('height', 'integer')
                        ->setMin(1);
            })

            ->validate($this->values);

        if($this->_block->shouldStoreDimensions()) {
            $this->_block->setWidth($validator['width']);
            $this->_block->setHeight($validator['height']);
        }

        $this->_block->setImageId($validator['image']);
        
        return $this->_block;
    }
}