<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\vector\svg;

use df;
use df\core;
use df\neon;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

// Base
class Shape implements Inspectable
{
    use TCustomContainerElement;
    use TStructure_Description;
    use TAttributeModule;
    use TAttributeModule_Shape;

    public static function factory($name, ...$args)
    {
        $name = ucfirst($name);

        if ($name == 'Rect') {
            $name = 'Rectangle';
        }

        $class = 'df\\neon\\svg\\Shape_'.$name;

        if (!class_exists($class)) {
            throw new RuntimeException(
                'Shape type '.$name.' could not be found'
            );
        }

        $ref = new \ReflectionClass($class);
        return $ref->newInstanceArgs($args);
    }

    public static function fromAttributes($name, array $attributes)
    {
        switch ($name) {
            case 'circle':
                $output = new Shape_Circle(
                    self::_extractInputAttribute($attributes, 'r', 0),
                    self::_extractInputAttribute($attributes, 'x', 0),
                    self::_extractInputAttribute($attributes, 'y', 0)
                );

                break;

            case 'ellipse':
                $output = new Shape_Ellipse(
                    self::_extractInputAttribute($attributes, 'rx', 0),
                    self::_extractInputAttribute($attributes, 'ry', 0),
                    self::_extractInputAttribute($attributes, 'x', 0),
                    self::_extractInputAttribute($attributes, 'y', 0)
                );

                break;

            case 'image':
                $output = new Shape_Image(
                    self::_extractInputAttribute($attributes, 'xlink:href', ''),
                    self::_extractInputAttribute($attributes, 'width', 0),
                    self::_extractInputAttribute($attributes, 'height', 0),
                    self::_extractInputAttribute($attributes, 'x', 0),
                    self::_extractInputAttribute($attributes, 'y', 0)
                );

                break;

            case 'line':
                $output = new Shape_Line(
                    self::_extractInputAttribute($attributes, 'x1', 0),
                    self::_extractInputAttribute($attributes, 'y1', 0),
                    self::_extractInputAttribute($attributes, 'x2', 0),
                    self::_extractInputAttribute($attributes, 'y2', 0)
                );

                break;

            case 'path':
                $output = new Shape_Path();

                break;

            case 'polygon':
                $output = new Shape_Polygon(
                    self::_extractInputAttribute($attributes, 'points', 0)
                );

                break;

            case 'polyline':
                $output = new Shape_Polyline(
                    self::_extractInputAttribute($attributes, 'points', 0)
                );

                break;

            case 'rect':
                $output = new Shape_Rectangle(
                    self::_extractInputAttribute($attributes, 'width', 0),
                    self::_extractInputAttribute($attributes, 'height', 0),
                    self::_extractInputAttribute($attributes, 'x', 0),
                    self::_extractInputAttribute($attributes, 'y', 0)
                );

                break;

            default:
                throw new InvalidArgumentException(
                    $name.' is not a recognized shape name'
                );
        }

        return $output;
    }

    public function getElementName()
    {
        return substr(strtolower($this->getName()), 6);
    }

    protected function _createPath(array $commands, array $exAttributes=[])
    {
        $output = new Shape_Path($commands);
        $attributes = $this->_attributes;

        foreach ($exAttributes as $ex) {
            unset($attributes[$ex]);
        }

        $output->applyInputAttributes($attributes);
        return $output;
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity->setSectionVisible('meta', true);

        foreach ($this->_attributes as $key => $value) {
            $entity->setMeta($key, $inspector($value));
        }

        if ($this->_title) {
            $entity->setProperty('*title', $inspector($this->_title));
        }

        if ($this->_description) {
            $entity->setProperty('*description', $inspector($this->_description));
        }
    }
}



// Circle
class Shape_Circle extends Shape implements ICircle, IPathProvider
{
    use TAttributeModule_Position;
    use TAttributeModule_Radius;

    public function __construct($radius, $x, $y=null)
    {
        $this->setRadius($radius);
        $this->setPosition($x, $y);
    }

    protected function _getXPositionAttributeName()
    {
        return 'cx';
    }

    protected function _getYPositionAttributeName()
    {
        return 'cy';
    }


    public function toPath()
    {
        $this->_position->convertRelativeAnchors();
        $r = clone $this->getRadius();

        $commands = [
            (new neon\vector\svg\command\Move($this->_position->getXOffset(), $this->_position->getYOffset())),
            (new neon\vector\svg\command\Move((-1 * $r->getValue()).$r->getUnit(), 0))->isRelative(true),
            (new neon\vector\svg\command\Arc($r, $r, 0, true, false, (2 * $r->getValue()).$r->getUnit(), 0))->isRelative(true),
            (new neon\vector\svg\command\Arc($r, $r, 0, true, false, (-2 * $r->getValue()).$r->getUnit(), 0))->isRelative(true),
            (new neon\vector\svg\command\ClosePath())->isRelative(true)
        ];

        return $this->_createPath($commands, ['cx', 'cy', 'r']);
    }
}



// Ellipse
class Shape_Ellipse extends Shape implements IEllipse, IPathProvider
{
    use TAttributeModule_Position;
    use TAttributeModule_2DRadius;

    public function __construct($xRadius, $yRadius, $position=null, $yPosition=null)
    {
        $this->setXRadius($xRadius);
        $this->setYRadius($yRadius);
        $this->setPosition($position, $yPosition);
    }

    protected function _getXPositionAttributeName()
    {
        return 'cx';
    }

    protected function _getYPositionAttributeName()
    {
        return 'cy';
    }

    public function toPath()
    {
        $this->_position->convertRelativeAnchors();
        $r1 = clone $this->getXRadius();
        $r2 = clone $this->getYRadius();

        $commands = [
            (new neon\vector\svg\command\Move($this->_position->getXOffset(), $this->_position->getYOffset())),
            (new neon\vector\svg\command\Move((-1 * $r1->getValue()).$r1->getUnit(), 0))->isRelative(true),
            (new neon\vector\svg\command\Arc($r1, $r2, 0, true, false, (2 * $r1->getValue()).$r1->getUnit(), 0))->isRelative(true),
            (new neon\vector\svg\command\Arc($r1, $r2, 0, true, false, (-2 * $r1->getValue()).$r1->getUnit(), 0))->isRelative(true),
            (new neon\vector\svg\command\ClosePath())->isRelative(true)
        ];

        return $this->_createPath($commands, ['cx', 'cy', 'rx', 'ry']);
    }
}



// Image
class Shape_Image extends Shape implements IImage
{
    use TAttributeModule_AspectRatio;
    use TAttributeModule_Dimension;
    use TAttributeModule_Position;
    use TAttributeModule_XLink;

    public function __construct($href, $width, $height, $position=null, $yPosition=null)
    {
        $this->setLinkHref($href);
        $this->setDimensions($width, $height);
        $this->setPosition($position, $yPosition);
    }
}




// Line
class Shape_Line extends Shape implements ILine, IPathProvider
{
    use TAttributeModule_PointData;

    const MIN_POINTS = 2;
    const MAX_POINTS = 2;

    public function __construct($points, $point2=null, $point3=null, $point4=null)
    {
        if ($point3 !== null) {
            $points = [$points, $point2];
            $point2 = [$point3, $point4];
        }

        if ($point2 !== null) {
            $points = [$points, $point2];
        }

        $this->setPoints($points);
    }

    protected function _onSetPoints()
    {
        $this->_setAttribute('x1', $this->_points[0]->getX());
        $this->_setAttribute('y1', $this->_points[0]->getY());
        $this->_setAttribute('x2', $this->_points[1]->getX());
        $this->_setAttribute('y2', $this->_points[1]->getY());
    }

    public function toPath()
    {
        $commands = [
            (new neon\vector\svg\command\Move($this->_getAttribute('x1'), $this->_getAttribute('y1'))),
            (new neon\vector\svg\command\Line($this->_getAttribute('x2'), $this->_getAttribute('y2'))),
        ];

        return $this->_createPath($commands, ['x1', 'y1', 'x2', 'y2']);
    }
}


// Path
class Shape_Path extends Shape implements IPath, IPathProvider
{
    use TAttributeModule_PathData;

    public function __construct($commands=null)
    {
        if ($commands !== null) {
            $this->setCommands($commands);
        }
    }

    public function toPath()
    {
        return clone $this;
    }
}



// Polygon
class Shape_Polygon extends Shape implements IPolygon, IPathProvider
{
    use TAttributeModule_PointData;

    const MIN_POINTS = 3;
    const MAX_POINTS = null;

    public function __construct($points)
    {
        $this->setPoints($points);
    }

    public function toPath()
    {
        $commands = [];
        $move = false;

        foreach ($this->_points as $point) {
            $point->convertRelativeAnchors();

            if (!$move) {
                $commands[] = new neon\vector\svg\command\Move($point->getXOffset(), $point->getYOffset());
                $move = true;
            } else {
                $commands[] = new neon\vector\svg\command\Line($point->getXOffset(), $point->getYOffset());
            }
        }

        $commands[] = new neon\vector\svg\command\ClosePath();

        return $this->_createPath($commands, ['points']);
    }
}



// Polyline
class Shape_Polyline extends Shape implements IPolyline, IPathProvider
{
    use TAttributeModule_PointData;

    const MIN_POINTS = 3;
    const MAX_POINTS = null;

    public function __construct($points)
    {
        $this->setPoints($points);
    }

    public function toPath()
    {
        $commands = [];
        $move = false;

        foreach ($this->_points as $point) {
            $point->convertRelativeAnchors();

            if (!$move) {
                $commands[] = new neon\vector\svg\command\Move($point->getXOffset(), $point->getYOffset());
                $move = true;
            } else {
                $commands[] = new neon\vector\svg\command\Line($point->getXOffset(), $point->getYOffset());
            }
        }

        return $this->_createPath($commands, ['points']);
    }
}



// Rectangle
class Shape_Rectangle extends Shape implements IRectangle, IPathProvider
{
    use TAttributeModule_Dimension;
    use TAttributeModule_Position;

    public function __construct($width, $height, $position=null, $yPosition=null)
    {
        $this->setDimensions($width, $height);
        $this->setPosition($position, $yPosition);
    }

    public function getElementName()
    {
        return 'rect';
    }

    public function toPath()
    {
        $this->_position->convertRelativeAnchors();
        $width = clone $this->getWidth();
        $height = clone $this->getHeight();

        $commands = [
            (new neon\vector\svg\command\Move($this->_position->getXOffset(), $this->_position->getYOffset())),
            (new neon\vector\svg\command\Line($width, 0))->isRelative(true),
            (new neon\vector\svg\command\Line(0, $height))->isRelative(true),
            (new neon\vector\svg\command\Line((-1 * $width->getValue()).$width->getUnit(), 0))->isRelative(true),
            (new neon\vector\svg\command\ClosePath())->isRelative(true)
        ];

        return $this->_createPath($commands, ['width', 'height', 'x', 'y']);
    }
}
