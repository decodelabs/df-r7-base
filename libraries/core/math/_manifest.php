<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\math;

use df;
use df\core;


// Tuple
interface ITuple extends core\collection\IIndexedCollection, core\collection\IAggregateIteratorCollection {
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



// Vector
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

    public function isZero();

    public function getSquareLength();

    public function setLength($length);
    public function setLengthNew($length);
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

    public function set2dAngle($angle, $type=IVector::DEGREES);
    public function rotate2d($angle, $type=IVector::DEGREES);
    public function set2dAngleNew($angle, $type=IVector::DEGREES);
    public function rotate2dNew($angle, $type=IVector::DEGREES);
    public function get2dAngle($type=IVector::DEGREES);
    public function set2dAngleFrom($vector, $angle, $type=IVector::DEGREES);
    public function rotate2dFrom($vector, $angle, $type=IVector::DEGREES);
    public function set2dAngleNewFrom($vector, $angle, $type=IVector::DEGREES);
    public function rotate2dNewFrom($vector, $angle, $type=IVector::DEGREES);
    public function get2dAngleFrom($vector, $type=IVector::DEGREES);

    public function getDotAngle($vector=null, $type=IVector::DEGREES);
}
