<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\math;

use df;
use df\core;
use df\math;

class Tuple implements ITuple, core\IDumpable {

    use core\collection\TArrayCollection;
    use core\collection\TArrayCollection_IndexedValueMap;
    use core\collection\TArrayCollection_Seekable;
    use core\collection\TArrayCollection_Sliceable;
    use core\collection\TArrayCollection_Shiftable;
    use core\collection\TArrayCollection_IndexedMovable;

    public function __construct(...$data) {
        $this->import(...$data);
    }

    protected function _normalizeValue($value) {
        if(!is_numeric($value)) {
            throw new InvalidArgumentException(
                'Invalid tuple value: '.$value
            );
        }

        if(!is_int($value)) {
            $value = (float)$value;
        }

        return $value;
    }

    public function getReductiveIterator() {
        return new ReductiveIndexIterator($this);
    }


    public function setSize($size) {
        $size = abs($size);
        $count = count($this->_collection);

        while($count < $size) {
            $this->_collection[] = 0;
            $count++;
        }

        if($count > $size) {
            $this->_collection = array_slice($this->_collection, 0, $size);
        }

        return $this;
    }

    public function getSize() {
        return count($this->_collection);
    }


    public function isZero() {
        foreach($this->_collection as $i => $value) {
            if($value != 0) {
                return false;
            }
        }

        return true;
    }


    public function getSum() {
        $output = 0;

        foreach($this->_collection as $value) {
            $output += $value;
        }

        return $output;
    }

    public function getProduct() {
        $output = 1;

        foreach($this->_collection as $value) {
            $output *= $value;
        }

        return $output;
    }


    public function getMin() {
        return min($this->_collection);
    }

    public function getMinIndex() {
        return $this->getIndex($this->getMin());
    }

    public function getMax() {
        return max($this->_collection);
    }

    public function getMaxIndex() {
        return $this->getIndex($this->getMax());
    }

    public function getMinMax() {
        return [$this->getMin(), $this->getMax()];
    }

    public function getMinMaxIndex() {
        return [$this->getMinIndex(), $this->getMaxIndex()];
    }


// Dump
    public function getDumpProperties() {
        return $this->_collection;
    }
}