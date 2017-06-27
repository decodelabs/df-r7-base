<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\fire\block;

use df;
use df\core;
use df\fire;
use df\arch;
use df\flex;
use df\aura;

class Markdown extends Base {

    const OUTPUT_TYPES = ['Html'];
    const DEFAULT_CATEGORIES = ['Description'];

    protected $_body;

    public function getFormat() {
        return 'markup';
    }

    public function setBody($body) {
        $this->_body = trim($body);
        return $this;
    }

    public function getBody() {
        return $this->_body;
    }


    public function isEmpty() {
        return !strlen(trim($this->_body));
    }

    public function getTransitionValue() {
        return $this->_body;
    }

    public function setTransitionValue($value) {
        $this->_body = $value;
        return $this;
    }



// Io
    public function readXml(flex\xml\IReadable $reader) {
        $this->_validateXmlReader($reader);

        $this->_body = $reader->getFirstCDataSection();
        return $this;
    }

    public function writeXml(flex\xml\IWritable $writer) {
        $this->_startWriterBlockElement($writer);
        $writer->writeCData($this->_body);
        $this->_endWriterBlockElement($writer);

        return $this;
    }


// Render
    public function render() {
        $view = $this->getView();

        return $view->html('div.block', $view->html->markdown($this->_body))
            ->setDataAttribute('type', $this->getName());
    }


// Form
    public function loadFormDelegate(arch\IContext $context, arch\node\IFormState $state, arch\node\IFormEventDescriptor $event, string $id): arch\node\IDelegate {
        return new class($this, ...func_get_args()) extends Base_Delegate {

            protected function setDefaultValues() {
                $this->values->body = $this->_block->getBody();
            }

            public function renderFieldContent(aura\html\widget\Field $field) {
                $this->view
                    ->linkCss('asset://lib/simplemde/simplemde.min.css', 100)
                    ->linkJs('asset://lib/simplemde/simplemde.min.js', 100);

                $field->push(
                    $ta = $this->html->textarea($this->fieldName('body'), $this->values->body)
                        //->isRequired($this->_isRequired)
                        ->setId($id = uniqid('markdown'))
                        ->addClass('w-editor markdown')
                );

                $this->view->addFootScript($id,
                    'var '.$id.' = new SimpleMDE({ element: $("#'.$id.'")[0] });'
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
        };
    }
}
