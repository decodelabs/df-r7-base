<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\mesh\job;

use df;
use df\core;
use df\mesh;

class Queue implements IQueue {

    protected $_jobs = [];
    protected $_transaction;
    protected $_isExecuting = false;

    public function __construct() {
        $this->_transaction = new Transaction();
    }

    public function getTransaction(): mesh\job\ITransaction {
        return $this->_transaction;
    }

    public function registerAdapter(mesh\job\ITransactionAdapter $adapter) {
        $this->_transaction->registerAdapter($adapter);
        return $this;
    }

    public function execute() {
        core\stub();
    }

    protected function _sortJobs() {
        uasort($this->_jobs, function($taskA, $taskB) {
            $aCount = $taskA ? $taskA->countDependencies() : 0;
            $bCount = $taskB ? $taskB->countDependencies() : 0;

            return $aCount > $bCount;
        });
    }
}