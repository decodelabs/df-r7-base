<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Nightfire\Block;

use DecodeLabs\Exemplar\Element as XmlElement;
use DecodeLabs\Exemplar\Writer as XmlWriter;
use DecodeLabs\R7\Nightfire\BlockAbstract;
use DecodeLabs\Tagged\Markup;

class Element extends BlockAbstract
{
    public const DEFAULT_CATEGORIES = [];

    protected ?string $slug = null;

    public function getFormat(): string
    {
        return 'structure';
    }


    /**
     * @return $this
     */
    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }


    public function isEmpty(): bool
    {
        return !strlen((string)$this->slug);
    }



    // Io
    protected function readXml(XmlElement $element): void
    {
        $this->slug = $element['slug'];
    }

    protected function writeXml(XmlWriter $writer): void
    {
        $writer['slug'] = $this->slug;
    }


    // Render
    public function render(): ?Markup
    {
        $view = $this->getView();
        return $view->nightfire->renderElement($this->slug);
    }
}
