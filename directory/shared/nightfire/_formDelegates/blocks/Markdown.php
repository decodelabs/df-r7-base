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

class Markdown extends Base {

    protected function setDefaultValues() {
        $this->values->body = $this->_block->getBody();
    }

    public function renderFieldAreaContent(aura\html\widget\FieldArea $fieldArea) {
        $this->view
            ->linkCss('asset://lib/simplemde/simplemde.min.css')
            ->linkJs('asset://lib/simplemde/simplemde.min.js');

        $fieldArea->push(
            $ta = $this->html->textarea(
                    $this->fieldName('body'),
                    $this->values->body
                )
                ->isRequired($this->_isRequired)
                ->setId($id = uniqid('markdown'))
        );

        $this->view->addFootScript($id,
'var '.$id.' = new SimpleMDE({
    element: $("#'.$id.'")[0]

});'
        );

        return $this;
    }

    public function apply() {
        $this->_block->setBody($this->values['body']);
        return $this->_block;
    }
}