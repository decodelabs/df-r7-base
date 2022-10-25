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
use DecodeLabs\R7\Nightfire\Block;
use DecodeLabs\R7\Nightfire\BlockAbstract;
use DecodeLabs\R7\Nightfire\BlockDelegateAbstract;
use DecodeLabs\Tagged as Html;
use DecodeLabs\Tagged\Markup;

class RawHtml extends BlockAbstract
{
    public const DEFAULT_CATEGORIES = ['Description'];

    protected ?string $content;

    public function getFormat(): string
    {
        return 'markup';
    }

    /**
     * @return $this
     */
    public function setHtmlContent(?string $content): static
    {
        $content = trim((string)$content);

        if (!strlen($content)) {
            $content = null;
        }

        $this->content = $content;
        return $this;
    }

    public function getHtmlContent(): ?string
    {
        return $this->content;
    }

    public function isEmpty(): bool
    {
        return $this->content === null;
    }

    public function getTransitionValue(): mixed
    {
        return $this->content;
    }

    public function setTransitionValue(mixed $value): static
    {
        $this->content = Coercion::toStringOrNull($value);
        return $this;
    }


    // Io
    protected function readXml(XmlElement $element): void
    {
        $this->content = $element->getFirstCDataSection();
    }

    protected function writeXml(XmlWriter $writer): void
    {
        $writer->writeCData($this->content);
    }



    // Render
    public function render(): ?Markup
    {
        $view = $this->getView();

        $content = preg_replace_callback('/ (href|src)\=\"([^\"]+)\"/', function ($matches) use ($view) {
            return ' '.$matches[1].'="'.$view->uri->__invoke($matches[2]).'"';
        }, (string)$this->content);

        return Html::{'div.block'}(Html::raw($content))
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
         * @extends BlockDelegateAbstract<RawHtml>
         */
        return new class ($this, ...func_get_args()) extends BlockDelegateAbstract {
            /**
             * @var RawHtml
             */
            protected Block $_block;

            protected function setDefaultValues(): void
            {
                $this->values->content = $this->_block->getHtmlContent();
            }

            public function renderFieldContent(FieldWidget $field): void
            {
                $field->push(
                    $this->html->textarea($this->fieldName('content'), $this->values->content)
                        ->isRequired($this->_isRequired)
                        ->addClass('editor html')
                );
            }

            public function apply(): Block
            {
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
