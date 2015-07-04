<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\math;

use df;
use df\core;
use df\math;

class Vector extends Tuple implements IVector {
    
    public static function create($size) {
        return new self(array_fill(0, $size, 0));
    }

    public static function factory($vector, $size=null) {
        if(!$vector instanceof IVector) {
            $vector = new self($vector);
        }

        if($size !== null) {
            $vector->setSize($size);
        }

        return $vector;
    }

    public function __get($member) {
        switch($member) {
            case 'size': return $this->count();
            case 'x': return $this->get(0);
            case 'y': return $this->get(1);
            case 'z': return $this->get(2);
        }
    }

    public function __set($member, $value) {
        switch($member) {
            case 'x': return $this->set(0, $value);
            case 'y': return $this->set(1, $value);
            case 'z': return $this->set(2, $value);
        }
    }

    public function setX($x) {
        return $this->set(0, $x);
    }

    public function getX() {
        return $this->get(0);
    }

    public function setY($y) {
        return $this->set(1, $y);
    }

    public function getY() {
        return $this->get(1);
    }

    public function setZ($z) {
        return $this->set(2, $z);
    }

    public function getZ() {
        return $this->get(2);
    }


    public function getSquareLength() {
        $output = 0;

        foreach($this->_collection as $value) {
            $output += pow($value, 2);
        }

        return $output;
    }

    public function getLength() {
        return sqrt($this->getSquareLength());
    }

    public function normalize() {
        $length = $this->getLength();

        foreach($this->_collection as $i => $value) {
            $this->_collection[$i] = $value / $length;
        }

        return $this;
    }

    public function normalizeNew() {
        $output = clone $this;
        return $output->normalize();
    }

    public function reverse() {
        $this->_collection = array_reverse($this->_collection);
        return $this;
    }

    public function reverseNew() {
        $output = clone $this;
        return $output->reverseNew();
    }

    public function scale($factor) {
        $factor = (float)$factor;

        foreach($this->_collection as $i => $value) {
            $this->_collection[$i] = $value * $factor;
        }

        return $this;
    }

    public function scaleNew($factor) {
        $output = clone $this;
        return $output->scale($factor);
    }


// Distance
    public function getDistance($vector, $type=IVector::CARTESIAN) {
        switch($type) {
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

    public function getCityDistance($vector) {
        return $this->getManhattanDistance($vector);
    }

    public function getManhattanDistance($vector) {
        $vector = self::factory($vector, $this->getSize());
        $output = 0;

        foreach($this->_collection as $i => $value) {
            $output += abs($value - $vector->_collection[$i]);
        }

        return $output;
    }

    public function getChessboardDistance($vector) {
        $vector = self::factory($vector, $this->getSize());
        $output = [];

        foreach($this->_collection as $i => $value) {
            $output[] = abs($value - $vector->_collection[$i]);
        }

        return max($output);
    }

    public function getCartesianDistance($vector) {
        $vector = self::factory($vector, $this->getSize());
        $output = 0;

        foreach($this->_collection as $i => $value) {
            $output += pow($value - $vector->_collection[$i], 2);
        }

        return sqrt($output);
    }



// Math
    public function add($vector) {
        $vector = self::factory($vector, $this->getSize());

        foreach($this->_collection as $i => $value) {
            $this->_collection[$i] = $value + $vector->_collection[$i];
        }

        return $this;
    }

    public function addNew($vector) {
        $output = clone $this;
        return $output->add($vector);
    }

    public function subtract($vector) {
        $vector = self::factory($vector, $this->getSize());

        foreach($this->_collection as $i => $value) {
            $this->_collection[$i] = $value - $vector->_collection[$i];
        }

        return $this;
    }

    public function subtractNew($vector) {
        $output = clone $this;
        return $output->subtract($vector);
    }

    public function multiply($vector) {
        $vector = self::factory($vector, $this->getSize());

        foreach($this->_collection as $i => $value) {
            $this->_collection[$i] = $value * $vector->_collection[$i];
        }

        return $this;
    }

    public function multiplyNew($vector) {
        $output = clone $this;
        return $output->multiply($vector);
    }

    public function divide($vector) {
        $vector = self::factory($vector, $this->getSize());

        foreach($this->_collection as $i => $value) {
            $this->_collection[$i] = $value / $vector->_collection[$i];
        }

        return $this;
    }

    public function divideNew($vector) {
        $output = clone $this;
        return $output->divide($vector);
    }


// Product
    public function getDotProduct($vector) {
        $vector = self::factory($vector, $this->getSize());
        $output = 0;

        foreach($this->_collection as $i => $value) {
            $output += $value * $vector->_collection[$i];
        }

        return $output;
    }

    public function getCrossProduct($vector) {
        if(($size = $this->getSize()) != 3) {
            throw new RuntimeException(
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

    public function getTripleScalarProduct($vector1, $vector2) {
        if(($size = $this->getSize())) {
            throw new RuntimeException(
                'Cross product can only be calculated on a Vector3'
            );
        }

        $vector1 = self::factory($vector1, $size);
        $vector2 = self::factory($vector2, $size);

        return $this->getDotProduct($vector1->getCrossProduct($vector2));
    }

    public function getAngle($vector, $type=IVector::DEGREES) {
        $vector = self::factory($vector, $this->getSize());

        $left = $this->normalizeNew();
        $right = $vector->normalizeNew();

        $output = acos($left->getDotProduct($right));

        switch($type) {
            case IVector::RADIANS:
                return $output;

            default:
            case IVector::DEGREES:
                return rad2deg($output);
        }
    }


// Dump
    public function getDumpProperties() {
        $size = $this->getSize();

        if($size > 3) {
            return $this->_collection;
        }

        $output = [
            'x' => $this->_collection[0],
            'y' => $this->_collection[1]
        ];

        if($size == 3) {
            $output['z'] = $this->_collection[2];
        }

        return $output;
    }
}