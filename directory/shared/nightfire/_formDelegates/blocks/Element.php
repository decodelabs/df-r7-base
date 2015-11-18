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

class Element extends Base {

    protected function loadDelegates() {
        $this->loadDelegate('element', '~/content/elements/ElementSelector')
            ->isForOne(true)
            ->isRequired($this->_isRequired);
    }

    protected function setDefaultValues() {
        $slug = $this->_block->getSlug();
        $id = $this->data->content->element->select('id')
            ->where('slug', '=', $slug)
            ->toValue('id');

        $this['element']->setSelected($id);
    }

    public function renderFieldContent(aura\html\widget\Field $field) {
        $this['element']->renderFieldContent($field);

        return $this;
    }

    public function apply() {
        $id = $this['element']->apply();
        $slug = $this->data->content->element->select('slug')
            ->where('id', '=', $id)
            ->toValue('slug');

        $this->_block->setSlug($slug);
        return $this->_block;
    }
}