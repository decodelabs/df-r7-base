<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\uri;

use df;
use df\core;

class FilePath extends Path implements IFilePath {

    public function __construct($input=null, $autoCanonicalize=true) {
        parent::__construct($input, $autoCanonicalize);
    }

    public function isAbsolute(bool $flag=null) {
        if($flag !== null) {
            return parent::isAbsolute($flag);
        }

        return $this->hasWinDrive()
            || parent::isAbsolute();
    }

    public function hasWinDrive() {
        return isset($this->_values[0]) && preg_match('/^[a-zA-Z]\:$/', $this->_values[0]);
    }

    public function getWinDriveLetter() {
        if(!isset($this->_values[0])) {
            return null;
        }

        if(!preg_match('/^([a-zA-Z])\:$/', $this->_values[0], $matches)) {
            return null;
        }

        return strtolower($matches[1]);
    }

    public function toString() {
        $separator = $this->_separator;

        if($this->hasWinDrive()) {
            $this->_separator = '\\';
        } else {
            $this->_separator = '/';
        }

        $output = $this->_pathToString(false);
        $this->_separator = $separator;

        return $output;
    }
}