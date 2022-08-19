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

use DecodeLabs\Metamorph;
use DecodeLabs\Tagged as Html;
use DecodeLabs\Exemplar\Element as XmlElement;
use DecodeLabs\Exemplar\Writer as XmlWriter;

class Markdown extends Base
{
    public const DEFAULT_CATEGORIES = ['Description'];

    protected $_body;

    public function getFormat(): string
    {
        return 'markup';
    }

    public function setBody($body)
    {
        $this->_body = trim($body);
        return $this;
    }

    public function getBody()
    {
        return $this->_body;
    }


    public function isEmpty(): bool
    {
        return !strlen(trim($this->_body));
    }

    public function getTransitionValue()
    {
        return $this->_body;
    }

    public function setTransitionValue($value)
    {
        $this->_body = $value;
        return $this;
    }



    // Io
    protected function readXml(XmlElement $element): void
    {
        $this->_body = $element->getFirstCDataSection();
    }

    protected function writeXml(XmlWriter $writer): void
    {
        $writer->writeCData($this->_body);
    }


    // Render
    public function render()
    {
        $view = $this->getView();

        return Html::{'div.block'}(Metamorph::{'markdown.safe'}($this->_body))
            ->setDataAttribute('type', $this->getName());
    }


    // Form
    public function loadFormDelegate(
        arch\IContext $context,
        arch\node\IFormState $state,
        arch\node\IFormEventDescriptor $event,
        string $id
    ): arch\node\IDelegate {
        return new class ($this, ...func_get_args()) extends Base_Delegate {
            /**
             * @var Markdown
             */
            protected $_block;

            protected function setDefaultValues()
            {
                $this->values->body = $this->_block->getBody();
            }

            public function renderFieldContent(aura\html\widget\Field $field)
            {
                $this->view
                    ->linkCss('asset://lib/simplemde/simplemde.min.css', 100)
                    //->linkJs('asset://lib/simplemde/simplemde.min.js', 100)
                    ->dfKit->load('df-kit/markdown')
                    ;

                $field->push(
                    $ta = $this->html->textarea($this->fieldName('body'), $this->values->body)
                        //->isRequired($this->_isRequired)
                        ->addClass('editor markdown')
                        ->setDataAttribute('editor', 'markdown')
                );

                return $this;
            }

            public function apply()
            {
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
