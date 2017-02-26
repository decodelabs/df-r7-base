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
            $this->html->textarea($this->fieldName('content'), $this->values->content)
                ->isRequired($this->_isRequired)
                ->addClass('w-editor html')
        );

        return $this;
    }

    public function apply() {
        $validator = $this->data->newValidator()
            ->addField('content', 'text')
                ->isRequired($this->_isRequired)
            ->validate($this->values);

        $this->_block->setHtmlContent($validator['content']);
        return $this->_block;
    }
}