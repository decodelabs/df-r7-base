<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\svg;

use df;
use df\core;
use df\neon;
    


// Base
class Shape implements core\IDumpable {

    use TAttributeModule;
    use TAttributeModule_Shape;

    public static function factory($name, $arg1) {
        $name = ucfirst($name);
        $class = 'df\\neon\\svg\\Shape_'.$name;

        if(!class_exists($class)) {
            throw new RuntimeException(
                'Shape type '.$name.' could not be found'
            );
        }

        $args = array_slice(func_get_args(), 1);
        $ref = new \ReflectionClass($class);

        return $ref->newInstanceArgs($args);
    }

    public function getDumpProperties() {
        return $this->_attributes;
    }
}



// Circle
class Shape_Circle extends Shape implements ICircle {

    use TAttributeModule_Position;
    use TAttributeModule_Radius;

    public function __construct($radius, $x, $y=null) {
    	$this->setRadius($radius);
    	$this->setPosition($x, $y);
    }
}



// Ellipse
class Shape_Ellipse extends Shape implements IEllipse {

    use TAttributeModule_Position;
    use TAttributeModule_2DRadius;

    public function __construct($xRadius, $yRadius, $position=null, $yPosition=null) {
        $this->setXRadius($xRadius);
        $this->setYRadius($yRadius);
        $this->setPosition($position, $yPosition);
    }
}



// Image
class Shape_Image extends Shape implements IImage {

    use TAttributeModule_AspectRatio;
    use TAttributeModule_Dimension;
    use TAttributeModule_Position;
    use TAttributeModule_XLink;

    public function __construct($href, $width, $height, $position=null, $yPosition=null) {
        $this->setLinkHref($href);
        $this->setDimensions($width, $height);
        $this->setPosition($position, $yPosition);
    }
}




// Line
class Shape_Line extends Shape implements ILine {

    use TAttributeModule_PointData;

    const MIN_POINTS = 2;
    const MAX_POINTS = 2;

    public function __construct($points, $point2=null) {
        if($point2 !== null) {
            $points = [$points, $point2];
        }

        $this->setPoints($points);
    }

    protected function _onSetPoints() {
        $this->_setAttribute('x1', $this->_points[0]->getX());
        $this->_setAttribute('y1', $this->_points[0]->getY());
        $this->_setAttribute('x2', $this->_points[1]->getX());
        $this->_setAttribute('y2', $this->_points[1]->getY());
    }
}


// Path
class Shape_Path extends Shape implements IPath {

    use TAttributeModule_PathData;

    public function __construct($commands) {
        $this->setCommands($commands);
    }
}



// Polygon
class Shape_Polygon extends Shape implements IPolygon {

    use TAttributeModule_PointData;

    const MIN_POINTS = 3;
    const MAX_POINTS = null;

    public function __construct($points) {
        $this->setPoints($points);
    }
}



// Polyline
class Shape_Polyline extends Shape implements IPolyline {

    use TAttributeModule_PointData;

    const MIN_POINTS = 3;
    const MAX_POINTS = null;

    public function __construct($points) {
        $this->setPoints($points);
    }
}



// Rectangle
class Shape_Rectangle extends Shape implements IRectangle {

    use TAttributeModule_Dimension;
    use TAttributeModule_Position;

    public function __construct($width, $height, $position=null, $yPosition=null) {
        $this->setDimensions($width, $height);
        $this->setPosition($position, $yPosition);
    }
}



// Text
class Shape_Text extends Shape implements IText {

}