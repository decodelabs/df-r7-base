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
use DecodeLabs\Metamorph;
use DecodeLabs\R7\Nightfire\Block;
use DecodeLabs\R7\Nightfire\BlockAbstract;
use DecodeLabs\R7\Nightfire\BlockDelegateAbstract;
use DecodeLabs\Tagged as Html;
use DecodeLabs\Tagged\Markup;

class Markdown extends BlockAbstract
{
    public const DEFAULT_CATEGORIES = ['Description'];

    protected ?string $body = null;

    public function getFormat(): string
    {
        return 'markup';
    }

    /**
     * @return $this
     */
    public function setBody(?string $body): static
    {
        $body = trim((string)$body);

        if (!strlen($body)) {
            $body = null;
        }

        $this->body = $body;
        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }


    public function isEmpty(): bool
    {
        return $this->body === null;
    }

    public function getTransitionValue(): mixed
    {
        return $this->body;
    }

    public function setTransitionValue(mixed $value): static
    {
        $this->body = Coercion::toStringOrNull($value);
        return $this;
    }



    // Io
    protected function readXml(XmlElement $element): void
    {
        $this->body = $element->getFirstCDataSection();
    }

    protected function writeXml(XmlWriter $writer): void
    {
        $writer->writeCData($this->body);
    }


    // Render
    public function render(): ?Markup
    {
        $view = $this->getView();

        return Html::{'div.block'}(Metamorph::{'markdown.safe'}($this->body))
            ->setDataAttribute('type', $this->getName());
    }


    // Form
    public function loadFormDelegate(
        Context $context,
        FormState $state,
        FormEventDescriptor $event,
        string $id
    ): NodeDelegate {
        /**
         * @extends BlockDelegateAbstract<Markdown>
         */
        return new class ($this, ...func_get_args()) extends BlockDelegateAbstract {
            /**
             * @var Markdown
             */
            protected Block $block;

            protected function setDefaultValues(): void
            {
                $this->values->body = $this->block->getBody();
            }

            public function renderFieldContent(FieldWidget $field): void
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
            }

            public function apply(): Block
            {
                $validator = $this->data->newValidator()
                    ->addField('body', 'text')
                        ->isRequired($this->_isRequired)
                    ->validate($this->values);

                $this->block->setBody($validator['body']);
                return $this->block;
            }
        };
    }
}
