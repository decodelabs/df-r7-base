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
    public function setSize(int $size);
    public function getSize(): int;

    public function isZero(): bool;
    public function getSum(): float;
    public function getProduct(): float;

    public function getMin(): float;
    public function getMinIndex(): int;
    public function getMax(): float;
    public function getMaxIndex(): int;
    public function getMinMax(): array;
    public function getMinMaxIndex(): array;
}



// Vector
interface IVector extends ITuple {

    const CITY = 'city';
    const MANHATTAN = 'manhattan';
    const CHESSBOARD = 'chessboard';
    const CARTESIAN = 'catesian';

    const DEGREES = 'degrees';
    const RADIANS = 'radians';

    public static function create(int $size): IVector;
    public static function factory($vector, int $size=null): IVector;

    public function setX(float $x);
    public function getX(): float;
    public function setY(float $y);
    public function getY(): float;
    public function setZ(float $z);
    public function getZ(): float;

    public function isZero(): bool;

    public function getSquareLength(): float;

    public function setLength(float $length);
    public function setLengthNew(float $length);
    public function getLength(): float;

    public function normalize();
    public function normalizeNew(): IVector;
    public function reverse();
    public function reverseNew(): IVector;
    public function scale(float $factor);
    public function scaleNew(float $factor): IVector;

    public function getDistance($vector, string $type=IVector::CARTESIAN): float;
    public function getCityDistance($vector): float;
    public function getManhattanDistance($vector): float;
    public function getChessboardDistance($vector): float;
    public function getCartesianDistance($vector): float;

    public function add($vector);
    public function addNew($vector): IVector;
    public function subtract($vector);
    public function subtractNew($vector): IVector;
    public function multiply($vector);
    public function multiplyNew($vector): IVector;
    public function divide($vector);
    public function divideNew($vector): IVector;

    public function getDotProduct($vector): float;
    public function getCrossProduct($vector): float;
    public function getTripleScalarProduct($vector1, $vector2);

    public function set2dAngle(float $angle, string $type=IVector::DEGREES);
    public function rotate2d(float $angle, string $type=IVector::DEGREES);
    public function set2dAngleNew(float $angle, string $type=IVector::DEGREES): IVector;
    public function rotate2dNew(float $angle, string $type=IVector::DEGREES): IVector;
    public function get2dAngle(string $type=IVector::DEGREES): float;
    public function set2dAngleFrom($vector, float $angle, string $type=IVector::DEGREES);
    public function rotate2dFrom($vector, float $angle, string $type=IVector::DEGREES);
    public function set2dAngleNewFrom($vector, float $angle, string $type=IVector::DEGREES): IVector;
    public function rotate2dNewFrom($vector, float $angle, string $type=IVector::DEGREES): IVector;
    public function get2dAngleFrom($vector, string $type=IVector::DEGREES): float;

    public function getDotAngle($vector=null, string $type=IVector::DEGREES): float;
}



// Util
interface IUtil {
    public static function clampFloat(?float $number, float $min, float $max): ?float;
    public static function clampInt(?int $number, int $min, int $max): ?int;
    public static function clampDegrees(?float $degrees, float $min=null, float $max=null): ?float;
}
