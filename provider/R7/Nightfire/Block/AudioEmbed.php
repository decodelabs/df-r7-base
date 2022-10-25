<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Nightfire\Block;

use df\aura;

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

class AudioEmbed extends BlockAbstract
{
    public const DEFAULT_CATEGORIES = ['Article', 'Description'];

    protected ?string $embedCode = null;

    public function getFormat(): string
    {
        return 'audio';
    }

    /**
     * @return $this
     */
    public function setEmbedCode(?string $code): static
    {
        $code = trim((string)$code);

        if (!strlen($code)) {
            $code = null;
        }

        $this->embedCode = $code;
        return $this;
    }

    public function getEmbedCode(): ?string
    {
        return $this->embedCode;
    }


    public function isEmpty(): bool
    {
        return $this->embedCode === null;
    }

    public function getTransitionValue(): mixed
    {
        return $this->embedCode;
    }

    public function setTransitionValue(mixed $value): static
    {
        $this->embedCode = Coercion::toStringOrNull($value);
        return $this;
    }


    // Io
    protected function readXml(XmlElement $element): void
    {
        $this->embedCode = $element->getFirstCDataSection();
    }

    protected function writeXml(XmlWriter $writer): void
    {
        $writer->writeCData($this->embedCode);
    }


    // Render
    public function render(): ?Markup
    {
        $output = Html::$embed->audio($this->embedCode);

        if ($output) {
            $output = $output->render()
                ->addClass('block')
                ->setDataAttribute('type', $this->getName());
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
         * @extends BlockDelegateAbstract<AudioEmbed>
         */
        return new class ($this, ...func_get_args()) extends BlockDelegateAbstract {
            /**
             * @var AudioEmbed
             */
            protected Block $_block;

            protected function setDefaultValues(): void
            {
                $this->values->embed = $this->_block->getEmbedCode();
            }

            public function renderFieldContent(FieldWidget $field): void
            {
                $field->push(
                    $this->html->textarea(
                            $this->fieldName('embed'),
                            $this->values->embed
                        )
                        ->isRequired($this->_isRequired)
                );
            }

            public function apply(): Block
            {
                $this->_block->setEmbedCode(
                    Coercion::toStringOrNull($this->values['embed'])
                );

                return $this->_block;
            }
        };
    }
}