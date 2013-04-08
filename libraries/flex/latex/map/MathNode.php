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
    
class MathNode extends iris\map\Node implements flex\latex\IMathNode, core\IDumpable {

    use flex\latex\TNodeClassProvider;
    use flex\latex\TReferable;

    public $symbols;

    protected $_isInline = false;
    protected $_blockType;

    public function isInline($flag=null) {
        if($flag !== null) {
            $this->_isInline = (bool)$flag;
            return $this;
        }

        return $this->_isInline;
    }

    public function setBlockType($type) {
        $this->_blockType = $type;
        return $this;
    }

    public function getBlockType() {
        return $this->_blockType;
    }



    public function setSymbols($symbols) {
        $this->symbols = $symbols;
        return $this;
    }

    public function appendSymbols($symbols) {
        $this->symbols .= $symbols;
        return $this;
    }

    public function getSymbols() {
        return $this->symbols;
    }

    public function isEmpty() {
        return !strlen($this->symbols);
    }


// Dump
    public function getDumpProperties() {
        if($this->_isInline) {
            return $this->symbols;
        }

        return [
            'id' => $this->id,
            'math' => $this->symbols
        ];
    }
}