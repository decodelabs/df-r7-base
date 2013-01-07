<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\vcs\git;

use df;
use df\core;
use df\spur;
    
class Status implements IStatus, core\IDumpable {

    protected $_tracked = array();
    protected $_untracked = array();
    protected $_repository;

    public function __construct(IRepository $repository) {
        $this->_repository = $repository;
        $this->refresh();
    }

    public function refresh() {
        $this->_tracked = array();
        $this->_untracked = array();

        $result = $this->_repository->_runCommand('status', [
            '--porcelain'
        ]);

        if(!empty($result)) {
            foreach(explode("\n", $result) as $line) {
                $state = substr($line, 0, 2);
                $path = trim(substr($line, 2));

                if($state == '??') {
                    $this->_untracked[$path] = $state;
                } else {
                    $this->_tracked[$path] = $state;
                }
            }
        }
    }

    public function count() {
        return count($this->_tracked) + count($this->_untracked);
    }

    public function getTracked() {
        return $this->_tracked;
    }

    public function hasTracked() {
        return !empty($this->_tracked);
    }

    public function countTracked() {
        return count($this->_tracked);
    }

    public function getUntracked() {
        return $this->_untracked;
    }

    public function hasUntracked() {
        return !empty($this->_untracked);
    }

    public function countUntracked() {
        return count($this->_untracked);
    }

    public function hasFile($path) {
        return isset($this->_tracked[$path])
            || isset($this->_untracked[$path]);
    }

    public function getFileState($path) {
        if(isset($this->_tracked[$path])) {
            return $this->_tracked[$path];
        } else if(isset($this->_untracked[$path])) {
            return '??';
        }
    }

    public function isTracked($path) {
        return isset($this->_tracked[$path]);
    }

    public function isUntracked($path) {
        return isset($this->_untracked[$path]);
    }

// Dump
    public function getDumpProperties() {
        return array_merge($this->_tracked, $this->_untracked);
    }
}