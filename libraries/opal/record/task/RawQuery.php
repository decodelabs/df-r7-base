<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\record\task;

use df;
use df\core;
use df\opal;
use df\mesh;

class RawQuery extends mesh\job\Base {

    protected $_query;

    public function __construct($id, opal\query\IWriteQuery $query) {
        $this->_query = $query;
        $this->_setId($id);
    }

    public function getAdapter() {
        return $this->_query->getSource()->getAdapter();
    }

    public function getQuery() {
        return $this->_query;
    }

    public function execute() {
        $this->_query->execute();

        return $this;
    }
}