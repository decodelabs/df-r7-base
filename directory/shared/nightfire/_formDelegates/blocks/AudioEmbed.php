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

class AudioEmbed extends Base {

    protected function setDefaultValues() {
        $this->values->embed = $this->_block->getEmbedCode();
    }

    public function renderFieldContent(aura\html\widget\Field $field) {
        $field->push(
            $this->html->textarea(
                    $this->fieldName('embed'),
                    $this->values->embed
                )
                ->isRequired($this->_isRequired)
        );

        return $this;
    }

    public function apply() {
        $this->_block->setEmbedCode($this->values['embed']);
        return $this->_block;
    }
}