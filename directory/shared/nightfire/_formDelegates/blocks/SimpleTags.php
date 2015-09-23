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
    
class SimpleTags extends Base {

    protected function setDefaultValues() {
        $this->values->body = $this->_block->getBody();
    }

    public function renderFieldAreaContent(aura\html\widget\FieldArea $fieldArea) {
        $fieldArea->push(
            $this->html->textarea(
                    $this->fieldName('body'),
                    $this->values->body
                )
                ->isRequired($this->_isRequired)
        );

        return $this;
    }

    public function apply() {
        $this->_block->setBody($this->values['body']);
        return $this->_block;
    }
}