<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\math;

use df;
use df\core;
use df\math;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class Vector extends Tuple implements IVector
{
    public static function create(int $size): IVector
    {
        return new self(array_fill(0, $size, 0));
    }

    public static function factory($vector, int $size=null): IVector
    {
        if (!$vector instanceof IVector) {
            $vector = new self($vector);
        }

        if ($size !== null) {
            $vector->setSize($size);
        }

        return $vector;
    }

    public function __get($member)
    {
        switch ($member) {
            case 'size': return $this->count();
            case 'x': return $this->get(0);
            case 'y': return $this->get(1);
            case 'z': return $this->get(2);
        }
    }

    public function __set($member, $value)
    {
        switch ($member) {
            case 'x': return $this->set(0, $value);
            case 'y': return $this->set(1, $value);
            case 'z': return $this->set(2, $value);
        }
    }

    public function setX(float $x)
    {
        return $this->set(0, $x);
    }

    public function getX(): float
    {
        return $this->get(0);
    }

    public function setY(float $y)
    {
        return $this->set(1, $y);
    }

    public function getY(): float
    {
        return $this->get(1);
    }

    public function setZ(float $z)
    {
        return $this->set(2, $z);
    }

    public function getZ(): float
    {
        return $this->get(2);
    }


    public function isZero(): bool
    {
        foreach ($this->_collection as $i => $value) {
            if ($value != 0) {
                return false;
            }
        }

        return true;
    }


    public function getSquareLength(): float
    {
        $output = 0;

        foreach ($this->_collection as $value) {
            $output += pow($value, 2);
        }

        return $output;
    }


    public function setLength(float $length)
    {
        if (!$this->isZero()) {
            $scale = $length / $this->getLength();
            $this->scale($scale);
        }

        return $this;
    }

    public function setLengthNew(float $length)
    {
        $output = clone $this;
        return $output->setLength($length);
    }

    public function getLength(): float
    {
        return sqrt($this->getSquareLength());
    }



    public function normalize()
    {
        $length = $this->getLength();

        foreach ($this->_collection as $i => $value) {
            $this->_collection[$i] = $value / $length;
        }

        return $this;
    }

    public function normalizeNew(): IVector
    {
        $output = clone $this;
        return $output->normalize();
    }

    public function reverse()
    {
        $this->_collection = array_reverse($this->_collection);
        return $this;
    }

    public function reverseNew(): IVector
    {
        $output = clone $this;
        return $output->reverseNew();
    }

    public function scale(float $factor)
    {
        foreach ($this->_collection as $i => $value) {
            $this->_collection[$i] = $value * $factor;
        }

        return $this;
    }

    public function scaleNew(float $factor): IVector
    {
        $output = clone $this;
        return $output->scale($factor);
    }




    // Distance
    public function getDistance($vector, string $type=IVector::CARTESIAN): float
    {
        switch ($type) {
            case IVector::CITY:
                return $this->getCityDistance($vector);

            case IVector::MANHATTAN:
                return $this->getManhattanDistance($vector);

            case IVector::CHESSBOARD:
                return $this->getChessboardDistance($vector);

            default:
            case IVector::CARTESIAN:
                return $this->getCartesianDistance($vector);
        }
    }

    public function getCityDistance($vector): float
    {
        return $this->getManhattanDistance($vector);
    }

    public function getManhattanDistance($vector): float
    {
        $vector = self::factory($vector, $this->getSize());
        $output = 0;

        foreach ($this->_collection as $i => $value) {
            $output += abs($value - $vector->_collection[$i]);
        }

        return $output;
    }

    public function getChessboardDistance($vector): float
    {
        $vector = self::factory($vector, $this->getSize());
        $output = [];

        foreach ($this->_collection as $i => $value) {
            $output[] = abs($value - $vector->_collection[$i]);
        }

        return max($output);
    }

    public function getCartesianDistance($vector): float
    {
        $vector = self::factory($vector, $this->getSize());
        $output = 0;

        foreach ($this->_collection as $i => $value) {
            $output += pow($value - $vector->_collection[$i], 2);
        }

        return sqrt($output);
    }



    // Math
    public function add($vector)
    {
        $vector = self::factory($vector, $this->getSize());

        foreach ($this->_collection as $i => $value) {
            $this->_collection[$i] = $value + $vector->_collection[$i];
        }

        return $this;
    }

    public function addNew($vector): IVector
    {
        $output = clone $this;
        return $output->add($vector);
    }

    public function subtract($vector)
    {
        $vector = self::factory($vector, $this->getSize());

        foreach ($this->_collection as $i => $value) {
            $this->_collection[$i] = $value - $vector->_collection[$i];
        }

        return $this;
    }

    public function subtractNew($vector): IVector
    {
        $output = clone $this;
        return $output->subtract($vector);
    }

    public function multiply($vector)
    {
        $vector = self::factory($vector, $this->getSize());

        foreach ($this->_collection as $i => $value) {
            $this->_collection[$i] = $value * $vector->_collection[$i];
        }

        return $this;
    }

    public function multiplyNew($vector): IVector
    {
        $output = clone $this;
        return $output->multiply($vector);
    }

    public function divide($vector)
    {
        $vector = self::factory($vector, $this->getSize());

        foreach ($this->_collection as $i => $value) {
            $match = $vector->_collection[$i];

            if ($match === 0) {
                $this->_collection[$i] = 0;
            } else {
                $this->_collection[$i] = $value / $vector->_collection[$i];
            }
        }

        return $this;
    }

    public function divideNew($vector): IVector
    {
        $output = clone $this;
        return $output->divide($vector);
    }


    // Product
    public function getDotProduct($vector): float
    {
        $vector = self::factory($vector, $this->getSize());
        $output = 0;

        foreach ($this->_collection as $i => $value) {
            $output += $value * $vector->_collection[$i];
        }

        return $output;
    }

    public function getCrossProduct($vector): float
    {
        if (($size = $this->getSize()) != 3) {
            throw core\Error::{'EDomain'}(
                'Cross product can only be calculated on a Vector3'
            );
        }

        $vector = self::factory($vector, $size);

        return new self(
            $this->getY() * $vector->getZ() - $this->getZ() * $vector->getY(),
            $this->getZ() * $vector->getX() - $this->getX() * $vector->getZ(),
            $this->getX() * $vector->getY() - $this->getY() * $vector->getX()
        );
    }

    public function getTripleScalarProduct($vector1, $vector2): float
    {
        if (($size = $this->getSize()) != 3) {
            throw core\Error::{'EDomain'}(
                'Cross product can only be calculated on a Vector3'
            );
        }

        $vector1 = self::factory($vector1, $size);
        $vector2 = self::factory($vector2, $size);

        return $this->getDotProduct($vector1->getCrossProduct($vector2));
    }


    // Angles
    public function set2dAngle(float $angle, string $type=IVector::DEGREES)
    {
        return $this->set2dAngleFrom([0, 0], $angle, $type);
    }

    public function rotate2d(float $angle, string $type=IVector::DEGREES)
    {
        return $this->rotate2dFrom([0, 0], $angle, $type);
    }

    public function set2dAngleNew(float $angle, string $type=IVector::DEGREES): IVector
    {
        return $this->set2dAngleNewFrom([0, 0], $angle, $type);
    }

    public function rotate2dNew(float $angle, string $type=IVector::DEGREES): IVector
    {
        return $this->rotate2dNewFrom([0, 0], $angle, $type);
    }

    public function get2dAngle(string $type=IVector::DEGREES): float
    {
        return $this->get2dAngleFrom([0, 0], $type);
    }

    public function set2dAngleFrom($vector, float $angle, string $type=IVector::DEGREES)
    {
        $vector = self::factory($vector, $this->getSize());
        $this->subtract($vector);

        switch ($type) {
            case IVector::RADIANS:
                break;

            default:
            case IVector::DEGREES:
                $angle = deg2rad($angle);
                break;
        }

        $length = $this->getLength();

        if ($length != 0) {
            $this->setX(sin($angle) * $length);
            $this->setY(cos($angle) * $length);
        }

        $this->add($vector);

        return $this;
    }

    public function rotate2dFrom($vector, float $angle, string $type=IVector::DEGREES)
    {
        $vector = self::factory($vector, $this->getSize());

        $current = $this->get2dAngleFrom($vector, $type);
        return $this->set2dAngleFrom($vector, $current + $angle, $type);
    }

    public function set2dAngleNewFrom($vector, float $angle, string $type=IVector::DEGREES): IVector
    {
        $output = clone $this;
        return $output->set2dAngleFrom($vector, $angle, $type);
    }

    public function rotate2dNewFrom($vector, float $angle, string $type=IVector::DEGREES): IVector
    {
        $output = clone $this;
        return $output->rotate2dFrom($vector, $angle, $type);
    }

    public function get2dAngleFrom($vector, string $type=IVector::DEGREES): float
    {
        $point = $this->subtractNew($vector);
        $output = atan2($point->x, $point->y);

        switch ($type) {
            case IVector::RADIANS:
                return $output;

            default:
            case IVector::DEGREES:
                return rad2deg($output);
        }
    }

    public function getDotAngle($vector=null, string $type=IVector::DEGREES): float
    {
        if ($vector === null) {
            $vector = [0, 1];
        }

        $vector = self::factory($vector, $this->getSize());

        $left = $this->normalizeNew();
        $right = $vector->normalizeNew();

        $output = acos($left->getDotProduct($right));

        switch ($type) {
            case IVector::RADIANS:
                return $output;

            default:
            case IVector::DEGREES:
                return rad2deg($output);
        }
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $size = $this->getSize();

        if ($size > 3) {
            $entity->setValues($inspector->inspectList($this->_collection));
            return;
        }

        $output = [
            'x' => $this->_collection[0],
            'y' => $this->_collection[1]
        ];

        if ($size == 3) {
            $output['z'] = $this->_collection[2];
        }

        $entity->setValues($inspector->inspectList($output));
    }
}
