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

    protected function _normalizeValue($value): float {
        if(!is_numeric($value)) {
            throw core\Error::EArgument(
                'Invalid tuple value: '.$value
            );
        }

        return (float)$value;
    }

    public function getReductiveIterator(): \Iterator {
        return new ReductiveIndexIterator($this);
    }


    public function setSize(int $size) {
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

    public function getSize(): int {
        return count($this->_collection);
    }


    public function isZero(): bool {
        foreach($this->_collection as $i => $value) {
            if($value !== 0) {
                return false;
            }
        }

        return true;
    }


    public function getSum(): float {
        return array_sum($this->_collection);
    }

    public function getProduct(): float {
        $output = 1;

        foreach($this->_collection as $value) {
            $output *= $value;
        }

        return $output;
    }


    public function getMin(): float {
        return min($this->_collection);
    }

    public function getMinIndex(): int {
        return $this->getIndex($this->getMin());
    }

    public function getMax(): float {
        return max($this->_collection);
    }

    public function getMaxIndex(): int {
        return $this->getIndex($this->getMax());
    }

    public function getMinMax(): array {
        return [$this->getMin(), $this->getMax()];
    }

    public function getMinMaxIndex(): array {
        return [$this->getMinIndex(), $this->getMaxIndex()];
    }


// Dump
    public function getDumpProperties() {
        return $this->_collection;
    }
}
