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

class VideoEmbed extends Base
{
    const DEFAULT_CATEGORIES = ['Article', 'Description'];

    protected $_embedCode;

    public function getFormat(): string
    {
        return 'video';
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
    public function readXml(flex\xml\IReadable $reader)
    {
        $this->_validateXmlReader($reader);
        $this->_embedCode = $reader->getFirstCDataSection();

        return $this;
    }

    public function writeXml(flex\xml\IWritable $writer)
    {
        $this->_startWriterBlockElement($writer);
        $writer->writeCData($this->_embedCode);
        $this->_endWriterBlockElement($writer);

        return $this;
    }


    // Render
    public function render()
    {
        $view = $this->getView();

        if (!$view->consent->has('statistics')) {
            $output = $view->apex->template('cookies/#elements/VideoPlaceholder.html');
        } else {
            $output = $view->html->videoEmbed($this->_embedCode);

            if (!empty($output)) {
                $output = $output->render();
            }
        }

        if ($output) {
            $output = $view->html('div.block', $output)
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
