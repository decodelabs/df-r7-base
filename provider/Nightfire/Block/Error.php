<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Nightfire\Block;

use DecodeLabs\Exceptional;
use DecodeLabs\Exemplar\Element as XmlElement;
use DecodeLabs\Exemplar\Writer as XmlWriter;
use DecodeLabs\Genesis;
use DecodeLabs\R7\Nightfire\BlockAbstract;
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
            'Error loading block type: ' . $this->type
        ), 'error');

        if ($this->error) {
            $output->setDescription($this->error->getMessage());
        }

        return $output;
    }
}
