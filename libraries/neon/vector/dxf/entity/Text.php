<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\vector\dxf\entity;

use df;
use df\core;
use df\neon;

class Text implements neon\vector\dxf\ITextEntity {
    
    use neon\vector\dxf\TEntity;
    use neon\vector\dxf\TDrawingEntity;
    use neon\vector\dxf\TTextProvider;

    protected $_body;
    protected $_alignmentPoint1;
    protected $_alignmentPoint2;
    protected $_rotation = 0;
    protected $_style;
    protected $_horizontalJustification = 0;
    protected $_verticalJustification = 0;

    public function __construct($text, $point1) {
        $this->_height = 1;
        $this->setBody($text);
        $this->setAlignmentPoint1($point1);
    }

    public function getType() {
        return 'TEXT';
    }

    public function setBody($body) {
        $this->_body = (string)$body;
        return $this;
    }

    public function getBody() {
        return $this->_body;
    }

    public function setAlignmentPoint1($point) {
        $this->_alignmentPoint1 = core\math\Vector::factory($point, 3);
        return $this;
    }

    public function getAlignmentPoint1() {
        return $this->_alignmentPoint1;
    }

    public function setAlignmentPoint2($point) {
        if($point !== null) {
            $point = core\math\Vector::factory($point, 3);
        }

        $this->_alignmentPoint2 = $point;
        return $this;
    }

    public function getAlignmentPoint2() {
        return $this->_alignmentPoint2;
    }

    public function setRotation($rotation) {
        $this->_rotation = (float)$rotation;
        return $this;
    }

    public function getRotation() {
        return $this->_rotation;
    }

    public function setStyle($name) {
        $this->_style = $name;
        return $this;
    }

    public function getStyle() {
        return $this->_style;
    }


    public function setHorizontalJustification($justification) {
        $this->_horizontalJustification = (int)$justification;
        return $this;
    }

    public function getHorizontalJustification() {
        return $this->_horizontalJustification;
    }

    public function setVerticalJustification($justification) {
        $this->_verticalJustification = (int)$justification;
        return $this;
    }

    public function getVerticalJustification() {
        return $this->_verticalJustification;
    }

    public function toString() {
        $output = sprintf(" 1\n%s\n", $this->_body);
        $output .= neon\vector\dxf\Document::_writePoint($this->_alignmentPoint1, 0);
        $output .= neon\vector\dxf\Document::_writePoint($this->_alignmentPoint2, 1);
        $output .= sprintf(" 40\n%F\n", $this->_height ? $this->_height : 0);
        $output .= sprintf(" 41\n%F\n", $this->_widthFactor);
        $output .= sprintf(" 50\n%F\n", $this->_rotation);
        $output .= sprintf(" 51\n%F\n", $this->_obliqueAngle);

        $textFlags = 0;

        if($this->_mirrorX) {
            $textFlags &= 2;
        }

        if($this->_mirrorY) {
            $textFlags &= 4;
        }

        $output .= sprintf(" 71\n%u\n", $textFlags);
        $output .= sprintf(" 72\n%u\n", $this->_horizontalJustification);
        $output .= sprintf(" 73\n%u\n", $this->_verticalJustification);

        $output .= $this->_writeDrawingString();
        return $this->_writeBaseString($output);
    }
}