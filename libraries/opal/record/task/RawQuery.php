<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\record\task;

use df;
use df\core;
use df\opal;
    
class RawQuery implements ITask {

    use TTask;

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

    public function execute(opal\query\ITransaction $transaction) {
        $this->_query->setTransaction($transaction);
        $this->_query->execute();

        return $this;
    }
}