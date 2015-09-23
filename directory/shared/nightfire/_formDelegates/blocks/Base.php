<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\shared\nightfire\_formDelegates\blocks;

use df;
use df\core;
use df\apex;
use df\arch;
use df\fire;
    
abstract class Base extends arch\form\Delegate implements fire\block\IFormDelegate {

    use arch\form\TForm_InlineFieldRenderableDelegate;
    use arch\form\TForm_RequirableDelegate;

    protected $_isNested = false;
    protected $_block;

    public function setBlock(fire\block\IBlock $block) {
        $this->_block = $block;
        return $this;
    }

    public function getBlock() {
        return $this->_block;
    }

    public function isNested($flag=null) {
        if($flag !== null) {
            $this->_isNested = (bool)$flag;
            return $this;
        }

        return $this->_isNested;
    }
}