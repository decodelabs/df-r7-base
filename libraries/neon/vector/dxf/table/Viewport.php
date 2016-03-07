<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\vector\dxf\table;

use df;
use df\core;
use df\neon;

class Viewport implements neon\vector\dxf\IViewportTable {

    use neon\vector\dxf\TTable;
    use neon\vector\dxf\TViewControlTable;

    protected $_lowerLeft;
    protected $_upperRight;
    protected $_snapBase;
    protected $_snapSpacing;
    protected $_snapRotation = 0;
    protected $_snapEnabled = false;
    protected $_snapStyle = 0;
    protected $_snapIsoPair = 0;
    protected $_gridSpacing;
    protected $_gridEnabled = false;
    protected $_height = 10;
    protected $_aspectRatio = 1.5;
    protected $_zoom = 100;
    protected $_fastZoom = 1;

    public function getType() {
        return 'VPORT';
    }

    public function setLowerLeft($vector) {
        if($vector !== null) {
            $vector = core\math\Vector::factory($vector, 2);
        }

        $this->_lowerLeft = $vector;
        return $this;
    }

    public function getLowerLeft() {
        return $this->_lowerLeft;
    }

    public function setUpperRight($vector) {
        if($vector !== null) {
            $vector = core\math\Vector::factory($vector, 2);
        }

        $this->_upperRight = $vector;
        return $this;
    }

    public function getUpperRight() {
        return $this->_upperRight;
    }


    public function setSnapBase($vector) {
        if($vector !== null) {
            $vector = core\math\Vector::factory($vector, 2);
        }

        $this->_snapBase = $vector;
        return $this;
    }

    public function getSnapBase() {
        return $this->_snapBase;
    }

    public function setSnapSpacing($vector) {
        if($vector !== null) {
            $vector = core\math\Vector::factory($vector, 2);
        }

        $this->_snapSpacing = $vector;
        return $this;
    }


    public function getSnapSpacing() {
        return $this->_snapSpacing;
    }

    public function setSnapRotation($angle) {
        if($angle !== null) {
            $angle = (float)$angle;
        }

        $this->_snapRotation = $angle;
        return $this;
    }

    public function getSnapRotation() {
        return $this->_snapRotation;
    }

    public function isSnapEnabled(bool $flag=null) {
        if($flag !== null) {
            $this->_snapEnabled = $flag;
            return $this;
        }

        return $this->_snapEnabled;
    }

    public function setSnapStyle($style) {
        $this->_snapStyle = $style;
        return $this;
    }

    public function getSnapStyle() {
        return $this->_snapStyle;
    }

    public function setSnapIsoPair($pair) {
        $this->_snapIsoPair = $pair;
        return $this;
    }

    public function getSnapIsoPair() {
        return $this->_snapIsoPair;
    }


    public function setGridSpacing($vector) {
        if($vector !== null) {
            $vector = core\math\Vector::factory($vector, 2);
        }

        $this->_gridSpacing = $vector;
        return $this;
    }

    public function getGridSpacing() {
        return $this->_gridSpacing;
    }

    public function isGridEnabled(bool $flag=null) {
        if($flag !== null) {
            $this->_gridEnabled = $flag;
            return $this;
        }

        return $this->_gridEnabled;
    }


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

    public function setAspectRatio($ratio) {
        if($ratio !== null) {
            $ratio = (float)$ratio;
        }

        $this->_aspectRatio = $ratio;
        return $this;
    }

    public function getAspectRatio() {
        return $this->_aspectRatio;
    }


    public function setZoom($zoom) {
        if($zoom !== null) {
            $zoom = (int)$zoom;
        }

        $this->_zoom = $zoom;
        return $this;
    }

    public function getZoom() {
        return $this->_zoom;
    }

    public function setFastZoom($zoom) {
        if($zoom !== null) {
            $zoom = (int)$zoom;
        }

        $this->_fastZoom = $zoom;
        return $this;
    }

    public function getFastZoom() {
        return $this->_fastZoom;
    }


    public function toString() {
        $output = '';

        $output .= neon\vector\dxf\Document::_writePoint($this->_lowerLeft, 0, [0, 0]);
        $output .= neon\vector\dxf\Document::_writePoint($this->_upperRight, 1, [1, 1]);
        $output .= neon\vector\dxf\Document::_writePoint($this->_centerPoint, 2, [0, 0]);
        $output .= neon\vector\dxf\Document::_writePoint($this->_snapBase, 3, [0, 0]);
        $output .= neon\vector\dxf\Document::_writePoint($this->_snapSpacing, 4, [0, 0]);
        $output .= neon\vector\dxf\Document::_writePoint($this->_gridSpacing, 5, [0, 0]);
        $output .= neon\vector\dxf\Document::_writePoint($this->_targetDirection, 6, [0, 0, 1]);
        $output .= neon\vector\dxf\Document::_writePoint($this->_targetPoint, 7, [0, 0, 0]);

        $output .= sprintf(" 40\n%F\n", $this->_height);
        $output .= sprintf(" 41\n%F\n", $this->_aspectRatio);
        $output .= sprintf(" 42\n%F\n", $this->_lensLength);
        $output .= sprintf(" 43\n%F\n", $this->_frontClippingPlane);
        $output .= sprintf(" 44\n%F\n", $this->_backClippingPlane);
        $output .= sprintf(" 50\n%F\n", $this->_snapRotation);
        $output .= sprintf(" 51\n%F\n", $this->_twistAngle);
        $output .= sprintf(" 71\n%u\n", $this->_mode);
        $output .= sprintf(" 72\n%u\n", $this->_zoom);
        $output .= sprintf(" 73\n%u\n", $this->_fastZoom);
        $output .= sprintf(" 74\n%u\n", 0);
        $output .= sprintf(" 75\n%u\n", $this->_snapEnabled);
        $output .= sprintf(" 76\n%u\n", $this->_gridEnabled);
        $output .= sprintf(" 77\n%s\n", $this->_snapStyle);
        $output .= sprintf(" 78\n%s\n", $this->_snapIsoPair);

        return $this->_writeBaseString($output);
    }
}