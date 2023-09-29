<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\unit;

use DecodeLabs\Exceptional;

use DecodeLabs\Glitch\Dumpable;
use df\core;

class DisplayPosition implements IDisplayPosition, Dumpable
{
    use core\TStringProvider;

    protected $_xAnchor = null;
    protected $_xOffset;
    protected $_yAnchor = null;
    protected $_yOffset;
    protected $_allowPlainNumbers = false;

    public static function factory($position, $position2 = null, $allowPlainNumbers = false)
    {
        if ($position instanceof IDisplayPosition) {
            return $position;
        }

        return new self($position, $position2, $allowPlainNumbers);
    }

    public function __construct($position, $position2 = null, $allowPlainNumbers = false)
    {
        $this->_allowPlainNumbers = (bool)$allowPlainNumbers;
        $this->parse($position, $position2);
    }

    public function isEmpty(): bool
    {
        return false;
    }

    public function parse($position, $position2 = null)
    {
        if ($position2 !== null) {
            $this->setX($position);
            $this->setY($position2);

            return $this;
        }


        $parts = explode(' ', strtolower((string)$position));

        while (!empty($parts)) {
            $part = array_shift($parts);

            switch ($part) {
                case 'top':
                case 'bottom':
                    $this->setYAnchor($part);

                    if (isset($parts[0])
                    && !in_array($parts[0], ['left', 'right', 'center'])
                    && (isset($parts[1]) || $this->_xAnchor !== null)) {
                        $this->setYOffset(array_shift($parts));
                    }

                    break;

                case 'left':
                case 'right':
                    $this->setXAnchor($part);

                    if (isset($parts[0])
                    && !in_array($parts[0], ['top', 'bottom', 'center'])
                    && (isset($parts[1]) || $this->_yAnchor !== null)) {
                        $this->setXOffset(array_shift($parts));
                    }

                    break;

                case 'center':
                    if ($this->_xAnchor === null) {
                        $this->setXAnchor($part);
                    } else {
                        $this->setYAnchor($part);
                    }

                    break;

                default:
                    if ($this->_xOffset === null) {
                        $this->setXOffset($part);

                        if ($this->_xAnchor === null) {
                            $this->setXAnchor('left');
                        }
                    } else {
                        $this->setYOffset($part);

                        if ($this->_yAnchor === null) {
                            $this->setYAnchor('top');
                        }
                    }

                    break;
            }
        }

        if ($this->_yAnchor === null) {
            $this->setYAnchor('center');
        }

        return $this;
    }

    public function setX($value)
    {
        $parts = explode(' ', strtolower((string)$value), 2);

        if (isset($parts[1])) {
            $this->setXOffset(array_pop($parts));
            $this->setXAnchor(array_shift($parts));
        } elseif (in_array($value, ['left', 'right', 'center'])) {
            $this->setXAnchor($value);
        } else {
            $this->setXOffset($value);
            $this->setXAnchor('left');
        }

        return $this;
    }

    public function getX()
    {
        if ($this->_xOffset !== null) {
            $output = $this->_xOffset;

            if ($this->_xAnchor !== 'left') {
                $output = $this->_xAnchor . ' ' . $output;
            }
        } else {
            $output = $this->_xAnchor;
        }

        return $output;
    }

    public function setXAnchor($anchor)
    {
        $anchor = strtolower((string)$anchor);

        switch ($anchor) {
            case 'left':
            case 'right':
                break;

            case 'center':
                $this->_xOffset = null;
                break;

            default:
                $anchor = 'left';
                break;
        }

        $this->_xAnchor = $anchor;
        return $this;
    }

    public function getXAnchor()
    {
        return $this->_xAnchor;
    }

    public function setXOffset($offset)
    {
        if ($offset !== null) {
            $offset = DisplaySize::factory($offset, null, $this->_allowPlainNumbers);
        }

        $this->_xOffset = $offset;
        return $this;
    }

    public function getXOffset()
    {
        return $this->_xOffset;
    }

    public function setY($value)
    {
        $parts = explode(' ', strtolower((string)$value), 2);

        if (isset($parts[1])) {
            $this->setYOffset(array_pop($parts));
            $this->setYAnchor(array_shift($parts));
        } elseif (in_array($value, ['top', 'bottom', 'center'])) {
            $this->setYAnchor($value);
        } else {
            $this->setYOffset($value);
            $this->setYAnchor('top');
        }

        return $this;
    }

    public function getY()
    {
        if ($this->_yOffset !== null) {
            $output = $this->_yOffset;

            if ($this->_yAnchor !== 'top') {
                $output = $this->_yAnchor . ' ' . $output;
            }
        } else {
            $output = $this->_yAnchor;
        }

        return $output;
    }

    public function setYAnchor($anchor)
    {
        $anchor = strtolower((string)$anchor);

        switch ($anchor) {
            case 'top':
            case 'bottom':
                break;

            case 'center':
                $this->_yOffset = null;
                break;

            default:
                $anchor = 'top';
                break;
        }

        $this->_yAnchor = $anchor;
        return $this;
    }

    public function getYAnchor()
    {
        return $this->_yAnchor;
    }

    public function setYOffset($offset)
    {
        if ($offset !== null) {
            $offset = DisplaySize::factory($offset, null, $this->_allowPlainNumbers);
        }

        $this->_yOffset = $offset;
        return $this;
    }

    public function getYOffset()
    {
        return $this->_yOffset;
    }

    public function toString(): string
    {
        $output = $this->getX() . ' ' . $this->getY();

        if ($output == 'center center') {
            $output = 'center';
        }

        return $output;
    }

    public function toCssString(): string
    {
        return $this->toString();
    }

    public function isRelative()
    {
        return !$this->isAbsolute();
    }

    public function isAbsolute()
    {
        return
            $this->hasRelativeAnchor()
        || ($this->_xOffset && !$this->_xOffset->isAbsolute())
        || ($this->_yOffset && !$this->_yOffset->isAbsolute())
        ;
    }

    public function hasRelativeAnchor()
    {
        return $this->hasRelativeXAnchor() || $this->hasRelativeYAnchor();
    }

    public function hasRelativeXAnchor()
    {
        return $this->_xAnchor != 'left';
    }

    public function hasRelativeYAnchor()
    {
        return $this->_yAnchor != 'top';
    }

    public function convertRelativeAnchors($width = null, $height = null)
    {
        switch ($this->_xAnchor) {
            case 'center':
                $this->setXOffset('50%');
                break;

            case 'right':
                if (!$this->_xOffset || $this->_xOffset->isEmpty()) {
                    $this->setXOffset('100%');
                } else {
                    $this->_xOffset = $this->_convertOppositeAnchorOffset($this->_xOffset);
                }

                break;
        }

        $this->_xAnchor = 'left';

        switch ($this->_yAnchor) {
            case 'center':
                $this->setYOffset('50%');
                break;

            case 'bottom':
                if (!$this->_yOffset || $this->_yOffset->isEmpty()) {
                    $this->setYOffset('100%');
                } else {
                    $this->_yOffset = $this->_convertOppositeAnchorOffset($this->_yOffset);
                }

                break;
        }

        $this->_yAnchor = 'top';

        return $this;
    }

    protected function _convertOppositeAnchorOffset($offset, $parentDimension = null)
    {
        if ($offset->getUnit() == '%') {
            $offset->setValue(100 + $offset->getValue());
        } elseif ($offset->getUnit() == 'px' || $offset->getUnit() === null && $parentDimension !== null) {
            $parentDimension = DisplaySize::factory($parentDimension, null, $this->_allowPlainNumbers);
            $offset->setValue($offset->getValue() + $parentDimension->getValue());
        } else {
            throw Exceptional::Runtime(
                'Unable to convert relative anchor with current data'
            );
        }

        return $offset;
    }

    public function extractAbsolute($width, $height, $compositeWidth = null, $compositeHeight = null)
    {
        $output = clone $this;

        $width = DisplaySize::factory($width, null, $this->_allowPlainNumbers);
        $height = DisplaySize::factory($height, null, $this->_allowPlainNumbers);

        if (!$output->_xOffset) {
            $output->_xOffset = new DisplaySize('0px', null, $this->_allowPlainNumbers);
        }

        if (!$output->_yOffset) {
            $output->_yOffset = new DisplaySize('0px', null, $this->_allowPlainNumbers);
        }

        $compositeXOffset = 0;
        $compositeYOffset = 0;

        if ($compositeWidth !== null) {
            $compositeWidth = DisplaySize::factory($compositeWidth, null, $this->_allowPlainNumbers);
        }

        if ($compositeHeight !== null) {
            $compositeHeight = DisplaySize::factory($compositeHeight, null, $this->_allowPlainNumbers);
        }


        // Width
        if (!$output->_xOffset->isAbsolute()) {
            if ($compositeWidth !== null) {
                $compositeXOffset = $output->_xOffset->extractAbsolute($compositeWidth, null, $compositeWidth, $compositeHeight)->getPixels();
            }

            $output->_xOffset = $output->_xOffset->extractAbsolute($width, null, $width, $height);
        } elseif ($compositeWidth !== null) {
            switch ($output->_xAnchor) {
                case 'center':
                    $compositeXOffset = floor($compositeWidth->getPixels() / 2);
                    break;

                case 'right':
                    $compositeXOffset = $compositeWidth->getPixels();
                    break;
            }
        }

        $output->_xOffset->setPixels($output->_xOffset->getPixels() - $compositeXOffset);

        switch ($output->_xAnchor) {
            case 'center':
                $output->_xOffset->setPixels($output->_xOffset->getPixels() + floor($width->getPixels() / 2));
                break;

            case 'right':
                $output->_xOffset->setPixels($output->_xOffset->getPixels() + $width->getPixels());
                break;
        }

        $output->_xAnchor = 'left';



        // Height
        if (!$output->_yOffset->isAbsolute()) {
            if ($compositeHeight !== null) {
                $compositeYOffset = $output->_yOffset->extractAbsolute($compositeHeight, null, $compositeWidth, $compositeHeight)->getPixels();
            }

            $output->_yOffset = $output->_yOffset->extractAbsolute($height, null, $width, $height);
        } elseif ($compositeHeight !== null) {
            switch ($output->_yAnchor) {
                case 'center':
                    $compositeYOffset = floor($compositeHeight->getPixels() / 2);
                    break;

                case 'bottom':
                    $compositeYOffset = $compositeHeight->getPixels();
                    break;
            }
        }

        $output->_yOffset->setPixels($output->_yOffset->getPixels() - $compositeYOffset);

        switch ($this->_yAnchor) {
            case 'center':
                $output->_yOffset->setPixels($output->_yOffset->getPixels() + floor($height->getPixels() / 2));
                break;

            case 'bottom':
                $output->_yOffset->setPixels($output->_yOffset->getPixels() + $height->getPixels());
                break;
        }

        $output->_yAnchor = 'top';

        return $output;
    }

    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'text' => $this->toString();
    }
}
