<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\vector\dxf\table;

use df;
use df\core;
use df\neon;

class LineType implements neon\vector\dxf\ILineTypeTable {

    use neon\vector\dxf\TTable;

    protected $_description;
    protected $_alignmentCode = 65;
    protected $_elements = [];

    public function getType() {
        return 'LTYPE';
    }

    public function setDescription($description) {
        $this->_description = $description;
        return $this;
    }

    public function getDescription() {
        return $this->_description;
    }

    public function setAlignmentCode($code) {
        $this->_alignmentCode = (int)$code;
        return $this;
    }

    public function getAlignmentCode() {
        return $this;
    }



    public function newElement($length) {
        $this->addElement($output = (new LineType_Element($length)));
        return $output;
    }

    public function newTextElement($length, $text) {
        return $this->newElement($length)->setText($text);
    }

    public function newShapeElement($length, $shapeNumber) {
        return $this->newElement($length)->setShape($shapeNumber);
    }

    public function setElements(array $elements) {
        return $this->clearElements()->addElements($elements);
    }

    public function addElements(array $elements) {
        foreach($elements as $element) {
            if($element instanceof neon\vector\dxf\ILineTypeTableElement) {
                $this->addElement($element);
            }
        }

        return $this;
    }

    public function addElement(neon\vector\dxf\ILineTypeTableElement $element) {
        $this->_elements[] = $element;
        return $this;
    }

    public function hasElement(neon\vector\dxf\ILineTypeTableElement $element) {
        foreach($this->_elements as $test) {
            if($test === $element) {
                return true;
            }
        }

        return false;
    }

    public function removeElement(neon\vector\dxf\ILineTypeTableElement $element) {
        foreach($this->_elements as $i => $test) {
            if($test === $element) {
                unset($this->_elements[$i]);
                break;
            }
        }

        return $this;
    }

    public function getElements() {
        return $this->_elements;
    }

    public function clearElements() {
        $this->_elements = [];
        return $this;
    }


    public function toString() {
        $output = '';

        if($this->_description !== null) {
            $output .= sprintf(" 3\n%s\n", $this->_description);
        }

        if($this->_alignmentCode !== null) {
            $output .= sprintf(" 72\n%u\n", $this->_alignmentCode);
        }

        $length = 0;

        foreach($this->_elements as $element) {
            $length += abs($element->getLength());
        }

        $output .= sprintf(
            " 73\n%u\n 40\n%F\n",
            count($this->_elements),
            $length
        );

        $output .= implode($this->_elements);

        return $this->_writeBaseString($output);
    }
}


class LineType_Element implements neon\vector\dxf\ILineTypeTableElement {

    use core\TStringProvider;

    protected $_length = 1;
    protected $_shape;
    protected $_text;
    protected $_styleId;
    protected $_scale;
    protected $_rotation;
    protected $_isRotationAbsolute = false;
    protected $_offset;

    public function __construct($length) {
        $this->setLength($length);
    }

    public function setLength($length) {
        $this->_length = (float)$length;
        return $this;
    }

    public function getLength() {
        return $this->_length;
    }

    public function setShape($number) {
        if($number !== null) {
            $this->_text = null;
            $number = (int)$number;
        }

        $this->_shape = $number;
        return $this;
    }

    public function getShape() {
        return $this->_shape;
    }

    public function setText($text) {
        if($text !== null) {
            $this->_shape = null;
            $text = (string)$text;
        }

        $this->_text = $text;
        return $this;
    }

    public function getText() {
        return $this->_text;
    }


    public function setStyleId($id) {
        $this->_styleId = $id;
        return $this;
    }

    public function getStyleId() {
        return $this->_styleId;
    }


    public function setScale($scale) {
        if($scale !== null) {
            $scale = (float)$scale;
        }

        $this->_scale = $scale;
        return $this;
    }

    public function getScale() {
        return $this->_scale;
    }


    public function setRotation($angle) {
        if($angle !== null) {
            $angle = (float)$angle;
        }

        $this->_rotation = $angle;
        return $this;
    }

    public function getRotation() {
        return $this->_rotation;
    }

    public function isRotationAbsolute(bool $flag=null) {
        if($flag !== null) {
            $this->_isRotationAbsolute = $flag;
            return $this;
        }

        return $this->_isRotationAbsolute;
    }


    public function setOffset($vector) {
        if($vector !== null) {
            $vector = core\math\Vector::factory($vector, 2);
        }

        $this->_offset = $vector;
        return $this;
    }

    public function getOffset() {
        return $this->_offset;
    }

    public function toString() {
        $output = sprintf(" 49\n%F\n", $this->_length);
        $flags = 0;

        if($this->_isRotationAbsolute) {
            $flags &= 1;
        }

        if($this->_text !== null) {
            $flags &= 2;
        }

        if($this->_shape !== null) {
            $flags &= 4;
        }

        if($flags > 0) {
            $output .= sprintf(" 74\n%u\n", $flags);
            $output .= sprintf(" 75\n%u\n", $this->_shape ?? 0);

            if($this->_styleId !== null) {
                $output .= sprintf(" 340\n%s\n", $this->_styleId);
            }
        }

        if($this->_scale !== null) {
            $output .= sprintf(" 46\n%F\n", $this->_scale);
        }

        if($flags > 1) {
            $output .= sprintf(" 50\n%F\n", $this->_rotation);
        }

        if($this->_offset !== null) {
            if($x = $this->_offset->x) {
                $output .= sprintf(" 44\n%F\n", $x);
            }

            if($y = $this->_offset->y) {
                $output .= sprintf(" 45\n%F\n", $y);
            }
        }

        if($this->_text !== null) {
            $output .= sprintf(" 9\n%s\n", $this->_text);
        }

        return $output;
    }
}