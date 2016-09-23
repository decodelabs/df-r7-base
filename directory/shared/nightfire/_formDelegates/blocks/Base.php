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

abstract class Base extends arch\node\form\Delegate implements fire\block\IFormDelegate {

    use arch\node\TForm_InlineFieldRenderableDelegate;
    use core\constraint\TRequirable;

    protected $_isNested = false;
    protected $_block;

    public function setBlock(fire\block\IBlock $block) {
        $this->_block = $block;
        return $this;
    }

    public function getBlock() {
        return $this->_block;
    }

    public function isNested(bool $flag=null) {
        if($flag !== null) {
            $this->_isNested = $flag;
            return $this;
        }

        return $this->_isNested;
    }
}