<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Nightfire\Block;

use DecodeLabs\Coercion;
use DecodeLabs\Exemplar\Element as XmlElement;
use DecodeLabs\Exemplar\Writer as XmlWriter;
use DecodeLabs\Metamorph;
use DecodeLabs\R7\Nightfire\BlockAbstract;
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
}
