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

class MathNode extends iris\map\Node implements flex\latex\IMathNode
{
    use flex\latex\TNodeClassProvider;
    use flex\latex\TReferable;
    use flex\latex\TListedNode;

    public $symbols;

    protected $_isInline = false;
    protected $_blockType;

    public function isInline(bool $flag=null)
    {
        if ($flag !== null) {
            $this->_isInline = $flag;
            return $this;
        }

        return $this->_isInline;
    }

    public function setBlockType($type)
    {
        $this->_blockType = $type;
        return $this;
    }

    public function getBlockType()
    {
        return $this->_blockType;
    }



    public function setSymbols($symbols)
    {
        $this->symbols = $symbols;
        return $this;
    }

    public function appendSymbols($symbols)
    {
        $this->symbols .= $symbols;
        return $this;
    }

    public function getSymbols()
    {
        return $this->symbols;
    }

    public function isEmpty(): bool
    {
        return !strlen($this->symbols);
    }
}
