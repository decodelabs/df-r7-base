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
use DecodeLabs\R7\Nightfire\BlockAbstract;
use DecodeLabs\Tagged as Html;
use DecodeLabs\Tagged\Markup;

class LibraryImage extends BlockAbstract
{
    public const DEFAULT_CATEGORIES = ['Description'];

    protected ?string $imageId = null;
    protected ?string $alt = null;
    protected ?string $link = null;
    protected ?float $width = null;

    public function getFormat(): string
    {
        return 'image';
    }

    // Image

    /**
     * @return $this
     */
    public function setImageId(?string $id): static
    {
        $this->imageId = $id;
        return $this;
    }

    public function getImageId(): ?string
    {
        return $this->imageId;
    }


    // Alt

    /**
     * @return $this
     */
    public function setAltText(?string $alt): static
    {
        $this->alt = $alt;
        return $this;
    }

    public function getAltText(): ?string
    {
        return $this->alt;
    }


    // Link

    /**
     * @return $this
     */
    public function setLink(?string $link): ?static
    {
        $this->link = $link;
        return $this;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }


    // Width

    /**
     * Set width constraint
     *
     * @return $this
     */
    public function setWidth(?float $width): static
    {
        $this->width = $width;
        return $this;
    }

    /**
     * Get width
     */
    public function getWidth(): ?float
    {
        return $this->width;
    }


    // IO
    public function isEmpty(): bool
    {
        return empty($this->imageId);
    }

    protected function readXml(XmlElement $element): void
    {
        $this->imageId = $element->getAttribute('image');
        $this->alt = $element->getAttribute('alt');
        $this->setLink($element->getAttribute('href'));
        $this->width = Coercion::toFloatOrNull($element->getAttribute('width'));
    }

    protected function writeXml(XmlWriter $writer): void
    {
        $writer['image'] = $this->imageId;
        $writer['alt'] = $this->alt;

        if ($this->link) {
            $writer['href'] = $this->link;
        }

        $writer['width'] = $this->width;
    }


    // Render
    public function render(): ?Markup
    {
        $view = $this->getView();

        $url = $view->media->getImageUrl($this->imageId);
        $output = Html::image($url, $this->alt);

        if ($this->link) {
            $output = $view->html->link($this->link, $output);
        }

        $output
            ->addClass('block')
            ->setDataAttribute('type', $this->getName());

        if ($this->width !== null) {
            $output->setStyle('width', $this->width.'%');
        }

        return $output;
    }
}
