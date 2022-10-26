<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Nightfire\Block;

use df\arch\IContext as Context;
use df\arch\node\IDelegate as NodeDelegate;
use df\arch\node\form\State as FormState;
use df\arch\node\IFormEventDescriptor as FormEventDescriptor;
use df\aura\html\widget\Field as FieldWidget;

use DecodeLabs\Coercion;
use DecodeLabs\Exemplar\Element as XmlElement;
use DecodeLabs\Exemplar\Writer as XmlWriter;
use DecodeLabs\Exceptional;
use DecodeLabs\Genesis;
use DecodeLabs\R7\Nightfire\Block;
use DecodeLabs\R7\Nightfire\BlockAbstract;
use DecodeLabs\R7\Nightfire\BlockDelegateAbstract;
use DecodeLabs\Tagged\Markup;
use Throwable;

class Error extends BlockAbstract
{
    public const DEFAULT_CATEGORIES = [];

    protected ?Throwable $error = null;
    protected ?string $type = null;
    protected mixed $data = null;

    public function getFormat(): string
    {
        return 'structure';
    }

    public function isHidden(): bool
    {
        return true;
    }


    /**
     * @return $this
     */
    public function setError(Throwable $e = null): static
    {
        $this->error = $e;
        return $this;
    }

    public function getError(): ?Throwable
    {
        return $this->error;
    }


    /**
     * @return $this
     */
    public function setType(?string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }


    /**
     * @return $this
     */
    public function setData(mixed $data): static
    {
        $this->data = $data;
        return $this;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function getTransitionValue(): mixed
    {
        return $this->data;
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
    public function render(): ?Markup
    {
        $view = $this->getView();

        if (
            Genesis::$environment->isProduction() &&
            !$view->context->request->isArea('admin')
        ) {
            return null;
        }

        $output = $view->html->flashMessage($view->_(
            'Error loading block type: '.$this->type
        ), 'error');

        if ($this->error) {
            $output->setDescription($this->error->getMessage());
        }

        return $output;
    }



    // Form
    public function loadFormDelegate(
        Context $context,
        FormState $state,
        FormEventDescriptor $event,
        string $id
    ): NodeDelegate {
        /**
         * @extends BlockDelegateAbstract<Error>
         */
        return new class ($this, ...func_get_args()) extends BlockDelegateAbstract {
            /**
             * @var Error
             */
            protected Block $block;

            protected function setDefaultValues(): void
            {
                $this->setStore('type', $this->block->getType());
                $this->setStore('data', $this->block->getData());

                if ($error = $this->block->getError()) {
                    $this->setStore('message', $error->getMessage());
                }
            }

            protected function afterInit(): void
            {
                $this->block->setType(Coercion::toStringOrNull($this->getStore('type')));
                $this->block->setData($this->getStore('data'));
            }

            public function renderFieldContent(FieldWidget $field): void
            {
                $output = $this->html->flashMessage($this->_(
                    'Error loading block type: '.$this->getStore('type')
                ), 'error');

                $output->setDescription($this->getStore('message'));
                $this->block->setData($this->getStore('data'));

                $field->push($output);
            }

            public function apply(): Block
            {
                $this->values->addError('noentry', 'Must update block!');
                return $this->block;
            }
        };
    }
}
