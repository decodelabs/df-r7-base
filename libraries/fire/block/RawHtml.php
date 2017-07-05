<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\fire\block;

use df;
use df\core;
use df\fire;
use df\flex;
use df\arch;
use df\aura;

class RawHtml extends Base {

    const DEFAULT_CATEGORIES = ['Description'];

    protected $_content;

    public function getFormat(): string {
        return 'markup';
    }

    public function setHtmlContent($content) {
        $this->_content = trim($content);
        return $this;
    }

    public function getHtmlContent() {
        return $this->_content;
    }

    public function isEmpty(): bool {
        return !strlen(trim($this->_content));
    }

    public function getTransitionValue() {
        return $this->_content;
    }

    public function setTransitionValue($value) {
        $this->_content = $value;
        return $this;
    }


// Io
    public function readXml(flex\xml\IReadable $reader) {
        $this->_validateXmlReader($reader);

        $this->_content = $reader->getFirstCDataSection();
        return $this;
    }

    public function writeXml(flex\xml\IWritable $writer) {
        $this->_startWriterBlockElement($writer);
        $writer->writeCData($this->_content);
        $this->_endWriterBlockElement($writer);

        return $this;
    }



// Render
    public function render() {
        $view = $this->getView();

        $content = preg_replace_callback('/ (href|src)\=\"([^\"]+)\"/', function($matches) use($view) {
            return ' '.$matches[1].'="'.$view->uri->__invoke($matches[2]).'"';
        }, $this->_content);

        return $view->html('div.block', $view->html->string($content))
            ->setDataAttribute('type', $this->getName());
    }


// Form
    public function loadFormDelegate(arch\IContext $context, arch\node\IFormState $state, arch\node\IFormEventDescriptor $event, string $id): arch\node\IDelegate {
        return new class($this, ...func_get_args()) extends Base_Delegate {

            protected function setDefaultValues() {
                $this->values->content = $this->_block->getHtmlContent();
            }

            public function renderFieldContent(aura\html\widget\Field $field) {
                $field->push(
                    $this->html->textarea($this->fieldName('content'), $this->values->content)
                        ->isRequired($this->_isRequired)
                        ->addClass('editor html')
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
        };
    }
}
