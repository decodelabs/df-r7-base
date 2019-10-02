<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon;

use df;
use df\core;
use df\neon;
use df\aura;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class ColorStop implements IColorStop, Inspectable
{
    use core\TStringProvider;

    protected $_color;
    protected $_size;

    public static function factory($colorStop): IColorStop
    {
        if ($colorStop instanceof IColorStop) {
            return $colorStop;
        }

        $parts = explode(' ', $colorStop);
        $size = array_pop($parts);

        try {
            $size = core\unit\DisplaySize::factory($size);
            $color = implode(' ', $parts);
        } catch (aura\style\InvalidArgumentException $e) {
            $color = $colorStop;
            $size = null;
        }

        return new self($color, $size);
    }

    public function __construct($color, $size)
    {
        $this->setColor($color);
        $this->setSize($size);
    }

    public function setColor($color)
    {
        $this->_color = Color::factory($color);
        return $this;
    }

    public function getColor(): IColor
    {
        return $this->_color;
    }

    public function setSize($size)
    {
        if ($size !== null) {
            $size = core\unit\DisplaySize::factory($size);
        }

        $this->_size = $size;
        return $this;
    }

    public function getSize(): ?core\unit\IDisplaySize
    {
        return $this->_size;
    }

    public function toString(): string
    {
        $output = $this->_color->toCssString();

        if ($this->_size !== null) {
            $output .= ' '.$this->_size->toString();
        }

        return $output;
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity->setDefinition($this->toString());
    }
}
