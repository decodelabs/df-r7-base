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

    public function renderFieldContent(aura\html\widget\Field $field) {
        $field->push(
            $this->html->textarea($this->fieldName('body'), $this->values->body)
                ->isRequired($this->_isRequired)
                ->addClass('w-editor simpleTags')
        );

        return $this;
    }

    public function apply() {
        $validator = $this->data->newValidator()
            ->addField('body', 'text')
                ->isRequired($this->_isRequired)
            ->validate($this->values);

        $this->_block->setBody($validator['body']);
        return $this->_block;
    }
}