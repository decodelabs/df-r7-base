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

use DecodeLabs\Exemplar\Element as XmlElement;
use DecodeLabs\Exemplar\Writer as XmlWriter;

use DecodeLabs\Exceptional;

class Error extends Base
{
    public const DEFAULT_CATEGORIES = [];

    protected $_error;
    protected $_type;
    protected $_data;

    public function getFormat(): string
    {
        return 'structure';
    }

    public function isHidden(): bool
    {
        return true;
    }

    public function setError(\Throwable $e=null)
    {
        $this->_error = $e;
        return $this;
    }

    public function getError()
    {
        return $this->_error;
    }

    public function setType($type)
    {
        $this->_type = $type;
        return $this;
    }

    public function getType()
    {
        return $this->_type;
    }

    public function setData($data)
    {
        $this->_data = $data;
        return $this->_data;
    }

    public function getData()
    {
        return $this->_data;
    }

    public function getTransitionValue()
    {
        return $this->_data;
    }

    public function isEmpty(): bool
    {
        return false;
    }


    // Io
    protected function readXml(XmlElement $element): void
    {
    }

    protected function writeXml(XmlWriter $writer): void
    {
        throw Exceptional::Runtime(
            'Error block type cannot be saved to xml'
        );
    }



    // Render
    public function render()
    {
        $view = $this->getView();

        if (df\Launchpad::$app->isProduction() && !$view->context->request->isArea('admin')) {
            return null;
        }

        $output = $view->html->flashMessage($view->_(
            'Error loading block type: '.$this->_type
        ), 'error');

        if ($this->_error) {
            $output->setDescription($this->_error->getMessage());
        }

        return $output;
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
             * @var Error
             */
            protected $_block;

            protected function setDefaultValues()
            {
                $this->setStore('type', $this->_block->getType());
                $this->setStore('data', $this->_block->getData());

                if ($error = $this->_block->getError()) {
                    $this->setStore('message', $error->getMessage());
                }
            }

            protected function afterInit()
            {
                $this->_block->setType($this->getStore('type'));
                $this->_block->setData($this->getStore('data'));
            }

            public function renderFieldContent(aura\html\widget\Field $field)
            {
                $output = $this->html->flashMessage($this->_(
                    'Error loading block type: '.$this->getStore('type')
                ), 'error');

                $output->setDescription($this->getStore('message'));
                $this->_block->setData($this->getStore('data'));

                $field->push($output);

                return $this;
            }

            public function apply()
            {
                $this->values->addError('noentry', 'Must update block!');
            }
        };
    }
}
