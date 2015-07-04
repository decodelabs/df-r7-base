<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\math;

use df;
use df\core;

// Exceptions
interface IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
class RuntimeException extends \RuntimeException implements IException {}


// Interfaces
interface ITuple extends core\collection\IIndexedCollection {
    public function setSize($size);
    public function getSize();

    public function isZero();
    public function getSum();
    public function getProduct();

    public function getMin();
    public function getMinIndex();
    public function getMax();
    public function getMaxIndex();
    public function getMinMax();
    public function getMinMaxIndex();
}

interface IVector extends ITuple {

    const CITY = 'city';
    const MANHATTAN = 'manhattan';
    const CHESSBOARD = 'chessboard';
    const CARTESIAN = 'catesian';

    const DEGREES = 'degrees';
    const RADIANS = 'radians';

    public function setX($x);
    public function getX();
    public function setY($y);
    public function getY();
    public function setZ($z);
    public function getZ();

    public function getSquareLength();
    public function getLength();
    public function normalize();
    public function normalizeNew();
    public function reverse();
    public function reverseNew();
    public function scale($factor);
    public function scaleNew($factor);

    public function getDistance($vector, $type=IVector::CARTESIAN);
    public function getCityDistance($vector);
    public function getManhattanDistance($vector);
    public function getChessboardDistance($vector);
    public function getCartesianDistance($vector);

    public function add($vector);
    public function addNew($vector);
    public function subtract($vector);
    public function subtractNew($vector);
    public function multiply($vector);
    public function multiplyNew($vector);
    public function divide($vector);
    public function divideNew($vector);

    public function getDotProduct($vector);
    public function getCrossProduct($vector);
    public function getTripleScalarProduct($vector1, $vector2);

    public function getAngle($vector, $type=IVector::DEGREES);
}
