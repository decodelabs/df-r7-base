<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\vcs\git;

use df;
use df\core;
use df\spur;

class Tree implements ITree, core\IDumpable {

    protected $_id;
    protected $_name = null;
    protected $_mode = null;
    protected $_repository;

    public function __construct(ILocalRepository $repo, $id, $name=null) {
        $this->_id = $id;
        $this->_name = $name;
        $this->_repository = $repo;
    }

    public function getId() {
        return $this->_id;
    }

    public function _setName($name) {
        $this->_name = $name;
        return $this;
    }

    public function getName(): string {
        if($this->_name === null) {
            $this->_fetchData();
        }

        return $this->_name;
    }

    public function _setMode($mode) {
        $this->_mode = $mode;
        return $this;
    }

    public function getMode() {
        if($this->_mode === null) {
            $this->_fetchData();
        }

        return $this->_mode;
    }

    public function getObjects() {
        $result = $this->_repository->_runCommand('ls-tree', [
            $this->_id
        ]);

        $output = [];

        if(!empty($result)) {
            foreach(explode("\n", $result) as $line) {
                list($mode, $type, $id, $name) = explode(' ', str_replace("\t", ' ', $line), 4);

                switch($type) {
                    case 'blob':
                        $object = new File($this->_repository, $id, $name);
                        break;

                    case 'tree':
                        $object = new Tree($this->_repository, $id, $name);
                        break;
                }

                $output[] = $object->_setMode($mode);
            }
        }

        return $output;
    }

    public function getFiles() {
        $result = $this->_repository->_runCommand('ls-tree', [
            $this->_id
        ]);

        $output = [];

        if(!empty($result)) {
            foreach(explode("\n", $result) as $line) {
                list($mode, $type, $id, $name) = explode(' ', str_replace("\t", ' ', $line), 4);

                if($type == 'blob') {
                    $output[] = (new File($this->_repository, $id, $name))
                        ->_setMode($mode);
                }
            }
        }

        return $output;
    }

    public function getTrees() {
        $result = $this->_repository->_runCommand('ls-tree', [
            $this->_id
        ]);

        $output = [];

        if(!empty($result)) {
            foreach(explode("\n", $result) as $line) {
                list($mode, $type, $id, $name) = explode(' ', str_replace("\t", ' ', $line), 4);

                if($type == 'tree') {
                    $output[] = (new Tree($this->_repository, $id, $name))
                        ->_setMode($mode);
                }
            }
        }

        return $output;
    }

    protected function _fetchData() {
        $result = $this->_repository->_runCommand('ls-tree -r HEAD | grep ', [$this->_id]);

        list(
            $this->_mode,
            $type,
            $id,
            $this->_name
        ) = explode(' ', str_replace("\t", ' ', $result), 4);
    }

    public function getRepository() {
        return $this->_repository;
    }


// Dump
    public function getDumpProperties() {
        $output = ['id' => $this->_id];

        if($this->_name !== null) {
            $output['name'] = $this->_name;
        }

        if($this->_mode !== null) {
            $output['mode'] = $this->_mode;
        }

        return $output;
    }
}
