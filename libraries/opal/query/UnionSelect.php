<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;

class UnionSelect extends Select implements IUnionSelectQuery {
    
    protected $_union;
    protected $_isUnionDistinct = true;

    public function __construct(IUnionQuery $union, ISource $source) {
        $this->_union = $union;
        parent::__construct($union->getSourceManager(), $source);
    }

    public function isUnionDistinct($flag=null) {
        if($flag !== null) {
            $this->_isUnionDistinct = (bool)$flag;
            return $this;
        }

        return $this->_isUnionDistinct;
    }

    public function endUnion() {
        $this->_union->addQuery($this);
        return $this->_union;
    }

    public function with($field1=null) {
        $this->endUnion();

        return Initiator::factory($this->_sourceManager->getApplication())
            ->beginUnionSelect($this->_union, func_get_args(), true);
    }

    public function withAll($field1=null) {
        $this->endUnion();

        return Initiator::factory($this->_sourceManager->getApplication())
            ->beginUnionSelect($this->_union, func_get_args(), false);
    }
}