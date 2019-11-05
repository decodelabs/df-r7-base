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

use DecodeLabs\Tagged\Html;
use DecodeLabs\Tagged\Xml\Element as XmlElement;
use DecodeLabs\Tagged\Xml\Writer as XmlWriter;

class AudioEmbed extends Base
{
    const DEFAULT_CATEGORIES = ['Article', 'Description'];

    protected $_embedCode;

    public function getFormat(): string
    {
        return 'audio';
    }

    public function setEmbedCode($code)
    {
        $this->_embedCode = trim($code);
        return $this;
    }

    public function getEmbedCode()
    {
        return $this->_embedCode;
    }


    public function isEmpty(): bool
    {
        return !strlen(trim($this->_embedCode));
    }

    public function getTransitionValue()
    {
        return $this->_embedCode;
    }

    public function setTransitionValue($value)
    {
        $this->_embedCode = $value;
        return $this;
    }


    // Io
    protected function readXml(XmlElement $element): void
    {
        $this->_embedCode = $element->getFirstCDataSection();
    }

    protected function writeXml(XmlWriter $writer): void
    {
        $writer->writeCData($this->_embedCode);
    }


    // Render
    public function render()
    {
        $output = Html::$embed->audio($this->_embedCode);

        if ($output) {
            $output = $output->render()
                ->addClass('block')
                ->setDataAttribute('type', $this->getName());
        }

        return $output;
    }



    // Form
    public function loadFormDelegate(arch\IContext $context, arch\node\IFormState $state, arch\node\IFormEventDescriptor $event, string $id): arch\node\IDelegate
    {
        return new class($this, ...func_get_args()) extends Base_Delegate {
            protected function setDefaultValues()
            {
                $this->values->embed = $this->_block->getEmbedCode();
            }

            public function renderFieldContent(aura\html\widget\Field $field)
            {
                $field->push(
                    $this->html->textarea(
                            $this->fieldName('embed'),
                            $this->values->embed
                        )
                        ->isRequired($this->_isRequired)
                );

                return $this;
            }

            public function apply()
            {
                $this->_block->setEmbedCode($this->values['embed']);
                return $this->_block;
            }
        };
    }
}
