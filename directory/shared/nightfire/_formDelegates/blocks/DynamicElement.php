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
    
class DynamicElement extends Base {

    protected function loadDelegates() {
        $this->loadDelegate('element', '~/content/elements/ElementSelector')
            ->isForOne(true)
            ->isRequired($this->_isRequired);
    }

    protected function setDefaultValues() {
        $this->getDelegate('element')->setSelected($this->_block->getSlug());
    }

    public function renderFieldAreaContent(aura\html\widget\FieldArea $fieldArea) {
        $this->getDelegate('element')->renderFieldAreaContent($fieldArea);

        return $this;
    }

    public function apply() {
        $this->_block->setSlug($this->getDelegate('element')->apply());
        return $this->_block;
    }
}