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

class RawHtml extends Base {

    protected function setDefaultValues() {
        $this->values->content = $this->_block->getHtmlContent();
    }

    public function renderFieldContent(aura\html\widget\Field $field) {
        $field->push(
            $this->html->htmlEditor($this->fieldName('content'), $this->values->content)
                ->isRequired($this->_isRequired)
        );

        return $this;
    }

    public function apply() {
        $this->_block->setHtmlContent($this->values['content']);
        return $this->_block;
    }
}