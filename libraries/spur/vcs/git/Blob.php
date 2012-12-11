<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\vcs\git;

use df;
use df\core;
use df\spur;
    
class Blob implements IBlob {

    protected $_id;
    protected $_content = null;
    protected $_type = null;
    protected $_size = null;
    protected $_repository;

    public function __construct(IRepository $repo, $id) {
        $this->_id = $id;
        $this->_repository = $repo;
    }

    public function getId() {
        return $this->_id;
    }

    public function getContent() {
        if($this->_content === null) {
            $this->_content = trim($this->_repository->_runCommand('cat-file', [
                '-p',
                $this->_id
            ]));
        }

        return $this->_content;
    }

    public function getType() {
        if($this->_type === null) {
            $this->_type = trim($this->_repository->_runCommand('cat-file', [
                '-t',
                $this->_id
            ]));
        }

        return $this->_type;
    }

    public function getSize() {
        if($this->_size === null) {
            $this->_size = trim($this->_repository->_runCommand('cat-file', [
                '-s',
                $this->_id
            ]));
        }

        return $this->_size;
    }


    public function getRepository() {
        return $this->_repository;
    }
}