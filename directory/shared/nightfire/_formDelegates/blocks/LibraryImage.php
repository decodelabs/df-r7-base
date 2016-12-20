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
        $this['image']->setSelected($this->_block->getImageId());
        $this->values->link = $this->_block->getLink();
    }

    public function renderFieldContent(aura\html\widget\Field $field) {
        $field->addField($this->_('Library image'))->push($this['image']);

        $field->addField($this->_('Link URL'))->push(
            $this->html->textbox($this->fieldName('link'), $this->values->link)
        );

        return $this;
    }

    public function apply() {
        $validator = $this->data->newValidator()
            // Image
            ->addField('image', 'delegate')
                ->fromForm($this)

            // Link
            ->addField('link', 'text')

            ->validate($this->values);

        $this->_block->setImageId($validator['image']);
        $this->_block->setLink($validator['link']);

        return $this->_block;
    }
}