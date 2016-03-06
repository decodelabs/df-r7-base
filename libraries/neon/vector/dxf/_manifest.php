<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\vector\dxf;

use df;
use df\core;
use df\neon;

// Exceptions
interface IException {}


// Interfaces
interface IDocument extends IEntityCollection, core\IStringProvider {
    public function addComment($comment);
    public function getComments();
    public function clearComments();

    public function setHeaders(array $headers);
    public function addHeaders(array $headers);
    public function setHeader($key, $value);
    public function getHeader($key);
    public function hasHeader($key);
    public function removeHeader($key);
    public function getHeaders();
    public function clearHeaders();

    public function setClasses(array $classes);
    public function addClasses(array $classes);
    public function newClass($dxfName, $className, $appName);
    public function addClass(IAppClass $class);
    public function hasClass($dxfName);
    public function removeClass($dxfName);
    public function getClass($dxfName);
    public function getClasses();
    public function clearClasses();

    public function setTables(array $tables);
    public function addTables(array $tables);
    public function addTable(ITable $table);
    public function getTables();
    public function clearTables();

    public function newAppIdTable($name);
    public function newBlockRecordTable($name);
    public function newLayer($name);
    public function newLineType($name);
    public function newStyle($name);
    public function newView($name);
    public function newViewportTable($name);

    public function saveTo($file);
}



interface IAppClass extends core\IStringProvider {
    const NO_OPERATIONS = 0; // = No operations allowed (0)
    const ERASE = 1; // = Erase allowed (0x1)
    const TRANSFORM = 2; // = Transform allowed (0x2)
    const COLOR_CHANGE = 4; // = Color change allowed (0x4)
    const LAYER_CHANGE = 8; // = Layer change allowed (0x8)
    const LINETYPE_CHANGE = 16; // = Linetype change allowed (0x10)
    const LINETYPE_SCALE_CHANGE = 32; // = Linetype scale change allowed (0x20)
    const VISIBILITY_CHANGE = 64; // = Visibility change allowed (0x40)
    const NO_CLONING = 127; // = All operations except cloning allowed (0x7F)
    const CLONING = 128; // = Cloning allowed (0x80)
    const ALL_OPERATIONS = 255; // = All operations allowed (0xFF)
    const FORMAT_PROXY = 32768; // = R13 format proxy (0x8000)

    public function setDxfName($name);
    public function getDxfName();
    public function setClassName($name);
    public function getClassName();
    public function setAppName($name);
    public function getAppName();
    public function setProxyCapabilities($flag);
    public function getProxyCapabilities();
    public function hasProxyCapability($flag);
    public function wasProxy($flag=null);
    public function isEntity($flag=null);
}






## TABLES ##
interface ITable extends core\IStringProvider {
    public function getType();
    public function setName($name);
    public function getName();
    public function setHandle($handle);
    public function getHandle();
    public function setSubclassMarker($marker);
    public function getSubclassMarker();

    public function setFlags($flags);
    public function getFlags();
    public function hasFlag($flag);

    public function isFrozen($flag=null);
    public function isFrozenInNew($flag=null);
    public function isLocked($flag=null);
    public function isXrefDependent($flag=null);
    public function isXrefResolved($flag=null);
    public function wasReferencedOnLastEdit($flag=null);
}

trait TTable {

    use core\TStringProvider;

    protected $_name;
    protected $_handle;
    protected $_subclassMarker;
    protected $_flags = 0;

    public function __construct($name) {
        $this->setName($name);
    }

    public function setName($name) {
        $this->_name = $name;
        return $this;
    }

    public function getName() {
        return $this->_name;
    }

    public function setHandle($handle) {
        $this->_handle = $handle;
        return $this;
    }

    public function getHandle() {
        return $this->_handle;
    }

    public function setSubclassMarker($marker) {
        $this->_subclassMarker = $marker;
        return $this;
    }

    public function getSubclassMarker() {
        return $this->_subclassMarker;
    }

    public function setFlags($flags) {
        $this->_flags = (int)$flags;
        return $this;
    }

    public function getFlags() {
        return $this->_flags;
    }

    public function hasFlag($flag) {
        return $this->_flags & $flag == $flag;
    }

    public function isFrozen($flag=null) {
        if($flag !== null) {
            if((bool)$flag) {
                $this->_flags |= 1;
            } else {
                $this->_flags &= ~1;
            }

            return $this;
        }

        return $this->_flags & 1 == 1;
    }

    public function isFrozenInNew($flag=null) {
        if($flag !== null) {
            if((bool)$flag) {
                $this->_flags |= 2;
            } else {
                $this->_flags &= ~2;
            }

            return $this;
        }

        return $this->_flags & 2 == 2;
    }

    public function isLocked($flag=null) {
        if($flag !== null) {
            if((bool)$flag) {
                $this->_flags |= 4;
            } else {
                $this->_flags &= ~4;
            }

            return $this;
        }

        return $this->_flags & 4 == 4;
    }

    public function isXrefDependent($flag=null) {
        if($flag !== null) {
            if((bool)$flag) {
                $this->_flags |= 16;
            } else {
                $this->_flags &= ~16;
            }

            return $this;
        }

        return $this->_flags & 16 == 16;
    }

    public function isXrefResolved($flag=null) {
        if($flag !== null) {
            if((bool)$flag) {
                $this->isXrefDependent(true);
                $this->_flags |= 32;
            } else {
                $this->_flags &= ~32;
            }

            return $this;
        }

        return $this->_flags & 32 == 32;
    }

    public function wasReferencedOnLastEdit($flag=null) {
        if($flag !== null) {
            if((bool)$flag) {
                $this->_flags |= 64;
            } else {
                $this->_flags &= ~64;
            }

            return $this;
        }

        return $this->_flags & 64 == 64;
    }

    protected function _writeBaseString($inner=null) {
        $output = sprintf(" 0\n%s\n", $this->getType());

        if($this->_subclassMarker !== null) {
            $output .= sprintf(" 100\n%s\n", $this->_subclassMarker);
        }

        $output .= sprintf(
            " 2\n%s\n 70\n%s\n",
            $this->_name,
            $this->_flags
        );

        $output .= $inner;
        return $output;
    }
}

interface IAppIdTable extends ITable {}

interface IBlockRecordTable extends ITable {}

interface IDimStyleTable extends ITable {

}

interface ILayerTable extends ITable {
    public function setColor($color);
    public function getColor();
    public function setLineType($name);
    public function getLineType();
}

interface ILineTypeTable extends ITable {
    public function setDescription($description);
    public function getDescription();
    public function setAlignmentCode($code);
    public function getAlignmentCode();

    public function newElement($length);
    public function newTextElement($length, $text);
    public function newShapeElement($length, $shapeNumber);
    public function setElements(array $elements);
    public function addElements(array $elements);
    public function addElement(ILineTypeTableElement $element);
    public function hasElement(ILineTypeTableElement $element);
    public function removeElement(ILineTypeTableElement $element);
    public function getElements();
    public function clearElements();
}

interface ILineTypeTableElement extends core\IStringProvider {
    const RELATIVE = 'relative';
    const ABSOLUTE = 'absolute';

    public function setLength($length);
    public function getLength();
    public function setShape($number);
    public function getShape();
    public function setText($text);
    public function getText();
    public function setStyleId($id);
    public function getStyleId();

    public function setScale($scale);
    public function getScale();

    public function setRotation($radians);
    public function getRotation();
    public function isRotationAbsolute($flag=null);

    public function setOffset($vector);
    public function getOffset();
}

interface IStyleTable extends ITable, ITextProvider {
    public function setLastHeightUsed($height);
    public function getLastHeightUsed();
    public function setPrimaryFontFileName($fileName);
    public function getPrimaryFontFileName();
    public function setBigFontFileName($fileName);
    public function getBigFontFileName();
}

interface IUcsTable extends ITable {

}

interface IViewControlTable extends ITable {

    const OFF = 0;
    const PERSPECTIVE = 1; // = perspective view active
    const FRONT_CLIPPING = 2; // = front clipping on
    const BACK_CLIPPING = 4; // = back clipping on
    const UCS_FOLLOW = 8; // = UCS Follow mode on
    const FRONT_CLIP_NOT_AT_EYE = 16; // = Front clip not at eye

    public function setCenterPoint($vector);
    public function getCenterPoint();
    public function setTargetPoint($vector);
    public function getTargetPoint();
    public function setTargetDirection($vector);
    public function getTargetDirection();

    public function setLensLength($length);
    public function getLensLength();
    public function setFrontClippingPlane($plane);
    public function getFrontClippingPlane();
    public function setBackClippingPlane($plane);
    public function getBackClippingPlane();

    public function setTwistAngle($angle);
    public function getTwistAngle();

    public function setMode($mode);
    public function getMode();
    public function hasMode($mode);
}

trait TViewControlTable {

    protected $_centerPoint;
    protected $_targetPoint;
    protected $_targetDirection;
    protected $_lensLength = 50;
    protected $_frontClippingPlane = 0;
    protected $_backClippingPlane = 0;
    protected $_twistAngle = 0;
    protected $_mode = 0;

    public function setCenterPoint($vector) {
        $this->_centerPoint = core\math\Vector::factory($vector, 2);
        return $this;
    }

    public function getCenterPoint() {
        return $this->_centerPoint;
    }

    public function setTargetPoint($vector) {
        $this->_targetPoint = core\math\Vector::factory($vector, 3);
        return $this;
    }

    public function getTargetPoint() {
        return $this->_targetPoint;
    }

    public function setTargetDirection($vector) {
        $this->_targetDirection = core\math\Vector::factory($vector, 3);
        return $this;
    }

    public function getTargetDirection() {
        return $this->_targetDirection;
    }


    public function setLensLength($length) {
        $this->_lensLength = (float)$length;
        return $this;
    }

    public function getLensLength() {
        return $this->_lensLength;
    }

    public function setFrontClippingPlane($plane) {
        $this->_frontClippingPlane = (float)$plane;
        return $this;
    }

    public function getFrontClippingPlane() {
        return $this->_frontClippingPlane;
    }

    public function setBackClippingPlane($plane) {
        $this->_backClippingPlane = (float)$plane;
        return $this;
    }

    public function getBackClippingPlane() {
        return $this->_backClippingPlane;
    }


    public function setTwistAngle($angle) {
        if($angle !== null) {
            $angle = (float)$angle;
        }

        $this->_twistAngle = $angle;
        return $this;
    }

    public function getTwistAngle() {
        return $this->_twistAngle;
    }


    public function setMode($mode) {
        $this->_mode = (int)$mode;
        return $this;
    }

    public function getMode() {
        return $this->_mode;
    }

    public function hasMode($mode) {
        return $this->_mode & $mode == $mode;
    }
}

interface IViewTable extends ITable, IViewControlTable {

    public function setWidth($width);
    public function getWidth();
    public function setHeight($height);
    public function getHeight();
}

interface IViewportTable extends ITable, IViewControlTable {
    public function setLowerLeft($vector);
    public function getLowerLeft();
    public function setUpperRight($vector);
    public function getUpperRight();

    public function setSnapBase($vector);
    public function getSnapBase();
    public function setSnapSpacing($vector);
    public function getSnapSpacing();
    public function setSnapRotation($angle);
    public function getSnapRotation();
    public function isSnapEnabled($flag=null);
    public function setSnapStyle($style);
    public function getSnapStyle();
    public function setSnapIsoPair($pair);
    public function getSnapIsoPair();

    public function setGridSpacing($vector);
    public function getGridSpacing();
    public function isGridEnabled($flag=null);

    public function setHeight($height);
    public function getHeight();
    public function setAspectRatio($ratio);
    public function getAspectRatio();

    public function setZoom($zoom);
    public function getZoom();
    public function setFastZoom($zoom);
    public function getFastZoom();
}



interface ITextProvider {
    public function setHeight($height);
    public function getHeight();
    public function setWidthFactor($factor);
    public function getWidthFactor();
    public function setObliqueAngle($angle);
    public function getObliqueAngle();
    public function mirrorX($flag=null);
    public function mirrorY($flag=null);
}


trait TTextProvider {

    protected $_height = 0;
    protected $_widthFactor = 1;
    protected $_obliqueAngle = 0;
    protected $_mirrorX = false;
    protected $_mirrorY = false;

    public function setHeight($height) {
        if($height !== null) {
            $height = (float)$height;
        }

        $this->_height = $height;
        return $this;
    }

    public function getHeight() {
        return $this->_height;
    }

    public function setWidthFactor($factor) {
        if($factor !== null) {
            $factor = (float)$factor;
        }

        $this->_widthFactor = $factor;
        return $this->_widthFactor;
    }

    public function getWidthFactor() {
        return $this->_widthFactor;
    }

    public function setObliqueAngle($angle) {
        if($angle !== null) {
            $angle = (float)$angle;
        }

        $this->_obliqueAngle = $angle;
        return $this;
    }

    public function getObliqueAngle() {
        return $this->_obliqueAngle;
    }

    public function mirrorX($flag=null) {
        if($flag !== null) {
            $this->_mirrorX = (bool)$flag;
            return $this;
        }

        return $this->_mirrorX;
    }

    public function mirrorY($flag=null) {
        if($flag !== null) {
            $this->_mirrorY = (bool)$flag;
            return $this;
        }

        return $this->_mirrorY;
    }
}




## ENTITIES ##
interface IEntityCollection {
    public function setEntities(array $entities);
    public function addEntities(array $entities);
    public function addEntity(IEntity $entity);
    public function hasEntity(IEntity $entity);
    public function removeEntity(IEntity $entity);
    public function getEntities();
    public function clearEntities();


    public function newArc($centerPoint, $radius=null, $startAngle=null, $endAngle=null);
    public function newCircle($centerPoint, $radius=null);
    public function newLine($startPoint, $endPoint);
    public function newPolyLine(...$points);
    public function newSolid($point1, $point2=null, $point3=null, $point4=null);
    public function newText($body, $point);
}

trait TEntityCollection {

    protected $_entities = [];

    public function setEntities(array $entities) {
        return $this->clearEntities()->$this->addEntities($entities);
    }

    public function addEntities(array $entities) {
        foreach($entities as $entity) {
            $this->addEntity($entity);
        }

        return $this;
    }

    public function addEntity(IEntity $entity) {
        $this->_entities[] = $entity;
        return $this;
    }

    public function hasEntity(IEntity $entity) {
        foreach($this->_entities as $test) {
            if($test === $entity) {
                return true;
            }
        }

        return false;
    }

    public function removeEntity(IEntity $entity) {
        foreach($this->_entities as $i => $test) {
            if($test === $entity) {
                unset($this->_entities[$i]);
                break;
            }
        }

        return $this;
    }

    public function getEntities() {
        return $this->_entities;
    }

    public function clearEntities() {
        $this->_entities = [];
        return $this;
    }



    public function newArc($centerPoint, $radius=null, $startAngle=null, $endAngle=null) {
        $this->addEntity($output = new neon\vector\dxf\entity\Arc($centerPoint, $radius, $startAngle, $endAngle));
        return $output;
    }

    public function newCircle($centerPoint, $radius=null) {
        $this->addEntity($output = new neon\vector\dxf\entity\Circle($centerPoint, $radius));
        return $output;
    }

    public function newLine($startPoint, $endPoint) {
        $this->addEntity($output = new neon\vector\dxf\entity\Line($startPoint, $endPoint));
        return $output;
    }

    public function newPolyLine(...$points) {
        $this->addEntity($output = new neon\vector\dxf\entity\PolyLine($points));
        return $output;
    }

    public function newSolid($point1, $point2=null, $point3=null, $point4=null) {
        $this->addEntity($output = new neon\vector\dxf\entity\Solid($point1, $point2, $point3, $point4));
        return $output;
    }

    public function newText($body, $point) {
        $this->addEntity($output = new neon\vector\dxf\entity\Text($body, $point));
        return $output;
    }
}



interface IEntity extends core\IStringProvider {
    public function getType();
    public function setHandle($handle);
    public function getHandle();
    public function setSubclassMarker($marker);
    public function getSubclassMarker();

    public function isVisible($flag=null);
    public function isInPaperSpace($flag=null);
    public function setColor($color);
    public function getColor();

    public function setLineType($name);
    public function getLineType();
    public function setLineTypeScale($scale);
    public function getLineTypeScale();

    public function setLayer($name);
    public function getLayer();
}

trait TEntity {

    use core\TStringProvider;

    protected $_handle;
    protected $_subclassMarker;
    protected $_isVisible = true;
    protected $_isInPaperSpace = false;
    protected $_color = 1;
    protected $_lineType;
    protected $_lineTypeScale;
    protected $_layer = 0;

    public function setHandle($handle) {
        $this->_handle = $handle;
        return $this;
    }

    public function getHandle() {
        return $this->_handle;
    }

    public function setSubclassMarker($marker) {
        $this->_subclassMarker = $marker;
        return $this;
    }

    public function getSubclassMarker() {
        return $this->_subclassMarker;
    }


    public function isVisible($flag=null) {
        if($flag !== null) {
            $this->_isVisible = (bool)$flag;
            return $this;
        }

        return $this->_isVisible;
    }

    public function isInPaperSpace($flag=null) {
        if($flag !== null) {
            $this->_isInPaperSpace = (bool)$flag;
            return $this;
        }

        return $this->_isInPaperSpace;
    }

    public function setColor($color) {
        if($color !== null) {
            $color = (int)$color;
        }

        $this->_color = $color;
        return $this;
    }

    public function getColor() {
        return $this->_color;
    }


    public function setLineType($name) {
        $this->_lineType = $name;
        return $this;
    }

    public function getLineType() {
        return $this->_lineType;
    }

    public function setLineTypeScale($scale) {
        if($scale !== null) {
            $scale = (float)$scale;
        }

        $this->_lineTypeScale = $scale;
        return $this;
    }

    public function getLineTypeScale() {
        return $this->_lineTypeScale;
    }


    public function setLayer($name) {
        $this->_layer = $name;
        return $this;
    }

    public function getLayer() {
        return $this->_layer;
    }


    protected function _writeBaseString($inner=null) {
        $output = sprintf(" 0\n%s\n", $this->getType());

        if($this->_handle !== null) {
            $output .= sprintf(" 5\n%s\n", $this->_handle);
        }

        if($this->_subclassMarker !== null) {
            $output .= sprintf(" 100\n%s\n", $this->_subclassMarker);
        }

        $output .= sprintf(" 8\n%s\n", $this->_layer);
        $output .= sprintf(" 62\n%u\n", $this->_color);
        $output .= $inner;

        if($this->_isInPaperSpace) {
            $output .= sprintf(" 67\n%u\n", $this->_isInPaperSpace);
        }

        if($this->_lineType !== null) {
            $output .= sprintf(" 6\n%s\n", $this->_lineType);
        }

        if($this->_lineTypeScale !== null) {
            $output .= sprintf(" 48\n%F\n", $this->_lineTypeScale);
        }

        $output .= sprintf(" 60\n%u\n", !$this->_isVisible);

        return $output;
    }
}


// Types
interface IDrawingEntity {
    public function setThickness($thickness);
    public function getThickness();
    public function setExtrusionDirection($vector3);
    public function getExtrusionDirection();
}

trait TDrawingEntity {

    protected $_thickness = 0;
    protected $_extrusionDirection;

    public function setThickness($thickness) {
        if($thickness !== null) {
            $thickness = (float)$thickness;
        }

        $this->_thickness = $thickness;
        return $this;
    }

    public function getThickness() {
        return $this->_thickness;
    }

    public function setExtrusionDirection($vector) {
        if($vector !== null) {
            $vector = core\math\Vector::factory($vector, 3);
        }

        $this->_extrusionDirection = $vector;
        return $this;
    }

    public function getExtrusionDirection() {
        return $this->_extrusionDirection;
    }

    protected function _writeDrawingString() {
        $output = '';

        if($this->_thickness !== null) {
            $output .= sprintf(" 39\n%F\n", $this->_thickness);
        }

        if($this->_extrusionDirection) {
            $output .= neon\vector\dxf\Document::_writePoint($this->_extrusionDirection, 200);
        }

        return $output;
    }
}


interface IArcEntity extends IEntity, IDrawingEntity {
    public function setCenterPoint($vector);
    public function getCenterPoint();
    public function setRadius($radius);
    public function getRadius();
    public function setStartAngle($angle);
    public function getStartAngle();
    public function setEndAngle($angle);
    public function getEndAngle();
}

interface ICircleEntity extends IEntity, IDrawingEntity {
    public function setCenterPoint($vector);
    public function getCenterPoint();
    public function setRadius($radius);
    public function getRadius();
}

interface ILineEntity extends IEntity, IDrawingEntity {
    public function setStartPoint($vector);
    public function getStartPoint();
    public function setEndPoint($vector);
    public function getEndPoint();
}

interface IPolyLineEntity extends IEntity, IDrawingEntity {
    const FLAT = 0; // = No smooth surface fitted
    const QUADRATIC = 5; // = Quadratic B-spline surface
    const CUBIC = 6; // = Cubic B-spline surface
    const BEZIER = 8; // = Bezier surface

    public function setElevation($elevation);
    public function getElevation();

    public function setLineFlags($flags);
    public function getLineFlags();
    public function hasLineFlag($flag);
    public function isClosed($flag=null);
    public function hasCurveFitVertices($flag=null);
    public function hasSplineFitVertices($flag=null);
    public function is3dPolyLine($flag=null);
    public function is3dPolygonMesh($flag=null);
    public function isClosedPolygonMesh($flag=null);
    public function isPolyFaceMesh($flag=null);
    public function hasContinuousLineType($flag=null);

    public function setDefaultStartWidth($width);
    public function getDefaultStartWidth();
    public function setDefaultEndWidth($width);
    public function getDefaultEndWidth();

    public function setMVertexCount($count);
    public function getMVertexCount();
    public function setNVertexCount($count);
    public function getNVertexCount();
    public function setMSurfaceDensity($density);
    public function getMSurfaceDensity();
    public function setNSurfaceDensity($density);
    public function getNSurfaceDensity();
    public function setCurveType($type);
    public function getCurveType();

    public function newVertex($vector3);
    public function setVertices(array $vertices);
    public function addVertices(array $vertices);
    public function addVertex(IVertexEntity $vertex);
    public function hasVertex(IVertexEntity $vertex);
    public function removeVertex(IVertexEntity $vertex);
    public function getVertices();
    public function clearVertices();
}

interface ISolidEntity extends IEntity, IDrawingEntity {
    public function setPoints($point1, $point2, $point3, $point4);
    public function getPoints();

}

interface ITextEntity extends IEntity, IDrawingEntity, ITextProvider {

    const H_LEFT = 0; // = Left;
    const H_CENTER = 1; // = Center;
    const H_RIGHT = 2; // = Right
    const H_ALIGNED = 3; // = Aligned (if vertical alignment = 0)
    const H_MIDDLE = 4; // = Middle (if vertical alignment = 0)
    const H_FIT = 5; // = Fit (if vertical alignment = 0)

    const V_BASELINE = 0; // = Baseline;
    const V_BOTTOM = 1; // = Bottom;
    const V_MIDDLE = 2; // = Middle;
    const V_TOP = 3; // = Top

    public function setBody($body);
    public function getBody();
    public function setAlignmentPoint1($point);
    public function getAlignmentPoint1();
    public function setAlignmentPoint2($point);
    public function getAlignmentPoint2();
    public function setRotation($rotation);
    public function getRotation();
    public function setStyle($name);
    public function getStyle();

    public function setHorizontalJustification($justification);
    public function getHorizontalJustification();
    public function setVerticalJustification($justification);
    public function getVerticalJustification();
}

interface IVertexEntity extends IEntity {
    const CURVE_FITTING = 1; // = Extra vertex created by curve-fitting.
    const CURVE_FIT_TANGENT = 2; // = Curve-fit tangent defined for this vertex. A curve-fit tangent direction of 0 may be omitted from DXF output but is significant if this bit is set.
    const SPLINE = 8; // = Spline vertex created by spline-fitting
    const SPLINE_FRAME_CONTROL_POINT = 16; // = Spline frame control point
    const POLYLINE_3D = 32; // = 3D polyline vertex
    const POLYGON_MESH = 64; // = 3D polygon mesh
    const POLYFACE_MESH = 128; // = Polyface mesh vertex

    public function setPoint($vector3);
    public function getPoint();

    public function setStartWidth($width);
    public function getStartWidth();
    public function setEndWidth($width);
    public function getEndWidth();
    public function setBulge($bulge);
    public function getBulge();

    public function setFlags($flags);
    public function getFlags();
    public function hasFlag($flag);
    public function isCurveFitting($flag=null);
    public function hasCurveFitTangent($flag=null);
    public function isSpline($flag=null);
    public function isSplingControlPoint($flag=null);
    public function isPolyLine3d($flag=null);
    public function isPolygonMesh($flag=null);
    public function isPolyFaceMesh($flag=null);

    public function setCurveFitTangentDirection($direction);
    public function getCurveFitTangentDirection();

    public function setPolyFaceIndices(array $indices=null);
    public function getPolyFaceIndices();
}