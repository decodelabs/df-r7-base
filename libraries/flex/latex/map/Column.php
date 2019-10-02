<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex\latex\map;

use df;
use df\core;
use df\flex;
use df\iris;

class Column extends iris\map\Node implements flex\latex\IColumn
{
    protected $_alignment;
    protected $_paragraphSizing;
    protected $_leftBorder = false;
    protected $_rightBorder = false;

    public function setAlignment($align)
    {
        $this->_alignment = $align;
        return $this;
    }

    public function getAlignment()
    {
        return $this->_alignment;
    }

    public function setParagraphSizing($size)
    {
        $this->_paragraphSizing = core\unit\DisplaySize::factory($size);
        return $this;
    }

    public function getParagraphSizing()
    {
        return $this->_paragraphSizing;
    }

    public function hasLeftBorder(bool $flag=null)
    {
        if ($flag !== null) {
            if ($flag) {
                $this->_leftBorder = (int)$flag;
            } else {
                $this->_leftBorder = false;
            }

            return $this;
        }

        return $this->_leftBorder;
    }

    public function hasRightBorder(bool $flag=null)
    {
        if ($flag !== null) {
            if ($flag) {
                $this->_rightBorder = (int)$flag;
            } else {
                $this->_rightBorder = false;
            }

            return $this;
        }

        return $this->_rightBorder;
    }
}
