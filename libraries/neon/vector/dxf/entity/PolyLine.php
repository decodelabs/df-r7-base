<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\vector\dxf\entity;

use df;
use df\core;
use df\neon;

class PolyLine implements neon\vector\dxf\IPolyLineEntity {
    
    use neon\vector\dxf\TEntity;
    use neon\vector\dxf\TDrawingEntity;

    protected $_elevation = 0;
    protected $_lineFlags = 0;
    protected $_defaultStartWidth;
    protected $_defaultEndWidth;
    protected $_mVertexCount;
    protected $_nVertexCount;
    protected $_mSurfaceDensity;
    protected $_nSurfaceDensity;
    protected $_curveType = 0;
    protected $_vertices = [];

    public function __construct(array $vertices=null) {
        if($vertices) {
            $this->addVertices($vertices);
        }
    }

    public function getType() {
        return 'POLYLINE';
    }

    public function setElevation($elevation) {
        $this->_elevation = (float)$elevation;
        return $this;
    }

    public function getElevation() {
        return $this->_elevation;
    }


    public function setLineFlags($flags) {
        $this->_lineFlags = (int)$flags;
        return $this;
    }

    public function getLineFlags() {
        return $this->_lineFlags;
    }

    public function hasLineFlag($flag) {
        return $this->_lineFlags & $flag == $flag;
    }

    public function isClosed($flag=null) {
        if($flag !== null) {
            if((bool)$flag) {
                $this->_lineFlags |= 1;
            } else {
                $this->_lineFlags &= ~1;
            }

            return $this;
        }

        return $this->_lineFlags & 1 == 1;
    }

    public function hasCurveFitVertices($flag=null) {
        if($flag !== null) {
            if((bool)$flag) {
                $this->_lineFlags |= 2;
            } else {
                $this->_lineFlags &= ~2;
            }

            return $this;
        }

        return $this->_lineFlags & 2 == 2;
    }

    public function hasSplineFitVertices($flag=null) {
        if($flag !== null) {
            if((bool)$flag) {
                $this->_lineFlags |= 4;
            } else {
                $this->_lineFlags &= ~4;
            }

            return $this;
        }

        return $this->_lineFlags & 4 == 4;
    }

    public function is3dPolyLine($flag=null) {
        if($flag !== null) {
            if((bool)$flag) {
                $this->_lineFlags |= 8;
            } else {
                $this->_lineFlags &= ~8;
            }

            return $this;
        }

        return $this->_lineFlags & 8 == 8;
    }

    public function is3dPolygonMesh($flag=null) {
        if($flag !== null) {
            if((bool)$flag) {
                $this->_lineFlags |= 16;
            } else {
                $this->_lineFlags &= ~16;
            }

            return $this;
        }

        return $this->_lineFlags & 16 == 16;
    }

    public function isClosedPolygonMesh($flag=null) {
        if($flag !== null) {
            if((bool)$flag) {
                $this->_lineFlags |= 32;
            } else {
                $this->_lineFlags &= ~32;
            }

            return $this;
        }

        return $this->_lineFlags & 32 == 32;
    }

    public function isPolyFaceMesh($flag=null) {
        if($flag !== null) {
            if((bool)$flag) {
                $this->_lineFlags |= 64;
            } else {
                $this->_lineFlags &= ~64;
            }

            return $this;
        }

        return $this->_lineFlags & 64 == 64;
    }

    public function hasContinuousLineType($flag=null) {
        if($flag !== null) {
            if((bool)$flag) {
                $this->_lineFlags |= 64;
            } else {
                $this->_lineFlags &= ~64;
            }

            return $this;
        }

        return $this->_lineFlags & 64 == 64;
    }


    public function setDefaultStartWidth($width) {
        $this->_defaultStartWidth = (float)$width;
        return $this;
    }

    public function getDefaultStartWidth() {
        return $this->_defaultStartWidth;
    }

    public function setDefaultEndWidth($width) {
        $this->_defaultEndWidth = (float)$width;
        return $this;
    }

    public function getDefaultEndWidth() {
        return $this->_defaultEndWidth;
    }


    public function setMVertexCount($count) {
        $this->_mVertexCount = (int)$count;
        return $this;
    }

    public function getMVertexCount() {
        return $this->_mVertexCount;
    }

    public function setNVertexCount($count) {
        $this->_nVertexCount = (int)$count;
        return $this;
    }

    public function getNVertexCount() {
        return $this->_nVertexCount;
    }

    public function setMSurfaceDensity($density) {
        $this->_mSurfaceDensity = (int)$density;
        return $this;
    }

    public function getMSurfaceDensity() {
        return $this->_mSurfaceDensity;
    }

    public function setNSurfaceDensity($density) {
        $this->_nSurfaceDensity = (int)$density;
        return $this;
    }

    public function getNSurfaceDensity() {
        return $this->_nSurfaceDensity;
    }

    public function setCurveType($type) {
        $this->_curveType = (int)$type;
        return $this;
    }

    public function getCurveType() {
        return $this->_curveType;
    }


    public function newVertex($vector3) {
        $this->addVertex($output = new Vertex($vector3));
        return $output;
    }

    public function setVertices(array $vertices) {
        return $this->clearVertices()->addVertices($vertices);
    }

    public function addVertices(array $vertices) {
        foreach($vertices as $vertex) {
            if(!$vertex instanceof neon\vector\dxf\IVertexEntity) {
                $vertex = new Vertex($vertex);    
            }

            $this->addVertex($vertex);
        }

        return $this;
    }

    public function addVertex(neon\vector\dxf\IVertexEntity $vertex) {
        $this->_vertices[] = $vertex;
        return $this;
    }

    public function hasVertex(neon\vector\dxf\IVertexEntity $vertex) {
        foreach($this->_vertices as $test) {
            if($test === $vertex) {
                return true;
            }
        }

        return false;
    }

    public function removeVertex(neon\vector\dxf\IVertexEntity $vertex) {
        foreach($this->_vertices as $i => $test) {
            if($test === $vertex) {
                unset($this->_vertices[$i]);
                break;
            }
        }

        return $this;
    }

    public function getVertices() {
        return $this->_vertices;
    }

    public function clearVertices() {
        $this->_vertices = [];
        return $this;
    }

    public function toString() {
        $output = '';

        if($this->_elevation > 0) {
            $output = neon\vector\dxf\Document::_writePoint([0, 0, $this->_elevation], 0);
        }

        $output .= sprintf(" 70\n%u\n", $this->_lineFlags);

        if($this->_defaultStartWidth) {
            $output .= sprintf(" 40\n%F\n", $this->_defaultStartWidth);
        }

        if($this->_defaultEndWidth) {
            $output .= sprintf(" 41\n%F\n", $this->_defaultEndWidth);
        }

        if($this->_mVertexCount) {
            $output .= sprintf(" 71\n%u\n", $this->_mVertexCount);
        }

        if($this->_nVertexCount) {
            $output .= sprintf(" 72\n%u\n", $this->_nVertexCount);
        }

        if($this->_mSurfaceDensity) {
            $output .= sprintf(" 73\n%u\n", $this->_mSurfaceDensity);
        }

        if($this->_nSurfaceDensity) {
            $output .= sprintf(" 74\n%u\n", $this->_nSurfaceDensity);
        }

        if($this->_curveType) {
            $output .= sprintf(" 75\n%u\n", $this->_curveType);
        }

        $output .= $this->_writeDrawingString();
        $output .= " 66\n1\n";
        $output = $this->_writeBaseString($output);
        $output .= implode($this->_vertices);
        $output .= " 0\nSEQEND\n";

        return $output;
    }
}