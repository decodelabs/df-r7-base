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

class Heading extends BlockAbstract
{
    public const DEFAULT_CATEGORIES = ['Description'];

    public const OPTIONS = [
        1 => 'h1',
        2 => 'h2',
        3 => 'h3',
        4 => 'h4',
        5 => 'h5',
        6 => 'h6'
    ];

    protected ?string $heading = null;
    protected int $level = 3;
    protected ?string $class = null;

    /**
     * @var array<string, string>
     */
    protected ?array $classOptions = null;

    public function getDisplayName(): string
    {
        return 'Heading';
    }

    public function getFormat(): string
    {
        return 'structure';
    }

    // Heading

    /**
     * @return $this
     */
    public function setHeading(?string $heading): static
    {
        $this->heading = $heading;
        return $this;
    }

    public function getHeading(): ?string
    {
        return $this->heading;
    }


    /**
     * @return $this
     */
    public function setHeadingLevel(int $level): static
    {
        $this->level = (int)$level;

        if ($this->level < 1) {
            $this->level = 1;
        } elseif ($this->level > 6) {
            $this->level = 6;
        }

        return $this;
    }

    public function getHeadingLevel(): int
    {
        return $this->level;
    }


    /**
     * @return $this
     */
    public function setHeadingClass(?string $class): static
    {
        $this->class = $class;
        return $this;
    }

    public function getHeadingClass(): ?string
    {
        return $this->class;
    }


    /**
     * @param array<string, string>|null $options
     * @return $this
     */
    public function setClassOptions(?array $options): static
    {
        $this->classOptions = $options;
        return $this;
    }

    /**
     * @return array<string, string>|null
     */
    public function getClassOptions(): ?array
    {
        return $this->classOptions;
    }


    // IO
    public function isEmpty(): bool
    {
        return !strlen(trim((string)$this->heading));
    }

    public function getTransitionValue(): mixed
    {
        return $this->heading;
    }

    public function setTransitionValue(mixed $value): static
    {
        $value = Coercion::toStringOrNull($value);

        if ($value !== null) {
            $value = (string)str_replace("\n", ' ', $value);
        }

        $this->heading = $value;
        return $this;
    }


    protected function readXml(XmlElement $element): void
    {
        $this->heading = $element->getFirstCDataSection();
        $this->level = Coercion::toIntOrNull($element['level']) ?? 3;
        $this->class = $element['class'];
    }

    protected function writeXml(XmlWriter $writer): void
    {
        $writer['level'] = $this->level;

        if ($this->class !== null) {
            $writer['class'] = $this->class;
        }

        $writer->writeCData($this->heading);
    }


    // Render
    public function render(): ?Markup
    {
        return Html::{'h' . $this->level}($this->heading)
            ->addClass($this->class)
            ->setDataAttribute('type', $this->getName());
    }
}
