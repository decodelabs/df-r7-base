<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\vector\dxf\entity;

use df;
use df\core;
use df\neon;

class Vertex implements neon\vector\dxf\IVertexEntity
{
    use neon\vector\dxf\TEntity;

    protected $_point;
    protected $_startWidth = 0;
    protected $_endWidth = 0;
    protected $_bulge = 0;
    protected $_flags = 0;
    protected $_curveFitTangentDirection = 0;
    protected $_polyFaceIndices;

    public function __construct($point)
    {
        $this->setPoint($point);
    }

    public function getType()
    {
        return 'VERTEX';
    }

    public function setPoint($point)
    {
        $this->_point = core\math\Vector::factory($point, 3);
        return $this;
    }

    public function getPoint()
    {
        return $this->_point;
    }


    public function setStartWidth($width)
    {
        $this->_startWidth = (float)$width;
        return $this;
    }

    public function getStartWidth()
    {
        return $this->_startWidth;
    }

    public function setEndWidth($width)
    {
        $this->_endWidth = (float)$width;
        return $this;
    }

    public function getEndWidth()
    {
        return $this->_endWidth;
    }

    public function setBulge($bulge)
    {
        $this->_bulge = (float)$bulge;
        return $this;
    }

    public function getBulge()
    {
        return $this->_bulge;
    }


    public function setFlags($flags)
    {
        $this->_flags = (int)$flags;
        return $this;
    }

    public function getFlags()
    {
        return $this->_flags;
    }

    public function hasFlag($flag)
    {
        return $this->_flags & $flag == $flag;
    }

    public function isCurveFitting(bool $flag=null)
    {
        if ($flag !== null) {
            if ($flag) {
                $this->_flags |= 1;
            } else {
                $this->_flags &= ~1;
            }

            return $this;
        }

        return $this->_flags & 1 == 1;
    }

    public function hasCurveFitTangent(bool $flag=null)
    {
        if ($flag !== null) {
            if ($flag) {
                $this->_flags |= 2;
            } else {
                $this->_flags &= ~2;
            }

            return $this;
        }

        return $this->_flags & 2 == 2;
    }

    public function isSpline(bool $flag=null)
    {
        if ($flag !== null) {
            if ($flag) {
                $this->_flags |= 8;
            } else {
                $this->_flags &= ~8;
            }

            return $this;
        }

        return $this->_flags & 8 == 8;
    }

    public function isSplingControlPoint(bool $flag=null)
    {
        if ($flag !== null) {
            if ($flag) {
                $this->_flags |= 16;
            } else {
                $this->_flags &= ~16;
            }

            return $this;
        }

        return $this->_flags & 16 == 16;
    }

    public function isPolyLine3d(bool $flag=null)
    {
        if ($flag !== null) {
            if ($flag) {
                $this->_flags |= 32;
            } else {
                $this->_flags &= ~32;
            }

            return $this;
        }

        return $this->_flags & 32 == 32;
    }

    public function isPolygonMesh(bool $flag=null)
    {
        if ($flag !== null) {
            if ($flag) {
                $this->_flags |= 64;
            } else {
                $this->_flags &= ~64;
            }

            return $this;
        }

        return $this->_flags & 64 == 64;
    }

    public function isPolyFaceMesh(bool $flag=null)
    {
        if ($flag !== null) {
            if ($flag) {
                $this->_flags |= 128;
            } else {
                $this->_flags &= ~128;
            }

            return $this;
        }

        return $this->_flags & 128 == 128;
    }


    public function setCurveFitTangentDirection($direction)
    {
        $this->_curveFitTangentDirection = (float)$direction;
        return $this;
    }

    public function getCurveFitTangentDirection()
    {
        return $this->_curveFitTangentDirection;
    }


    public function setPolyFaceIndices(array $indices=null)
    {
        $this->_polyFaceIndices = $indices;
        return $this;
    }

    public function getPolyFaceIndices()
    {
        return $this->_polyFaceIndices;
    }

    public function toString(): string
    {
        $output = neon\vector\dxf\Document::_writePoint($this->_point);

        if ($this->_startWidth) {
            $output .= sprintf(" 40\n%F\n", $this->_startWidth);
        }

        if ($this->_endWidth) {
            $output .= sprintf(" 41\n%F\n", $this->_endWidth);
        }

        if ($this->_bulge) {
            $output .= sprintf(" 42\n%F\n", $this->_bulge);
        }

        if ($this->_flags) {
            $output .= sprintf(" 70\n%u\n", $this->_flags);
        }

        if ($this->_curveFitTangentDirection) {
            $output .= sprintf(" 50\n%F\n", $this->_curveFitTangentDirection);
        }

        if ($this->_polyFaceIndices) {
            foreach (array_slice($this->_polyFaceIndices, 0, 4) as $i => $index) {
                $output .= sprintf(" %u\n%u\n", 71 + $i, $index);
            }
        }

        return $this->_writeBaseString($output);
    }
}
