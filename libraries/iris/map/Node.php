<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\iris\map;

use df;
use df\core;
use df\iris;
    
abstract class Node implements INode {

    protected $_location;
    protected $_comment;

    public function __construct(iris\ILocationProvider $locationProvider=null) {
        if($locationProvider) {
            $this->_location = $locationProvider->getLocation();
        } else {
            $this->_location = new iris\Location(null, 1, 1);
        }
    }

    public function getLine() {
        return $this->_location->getLine();
    }

    public function getColumn() {
        return $this->_location->getColumn();
    }

    public function getSourceUri() {
        return $this->_location->getSourceUri();
    }

    public function getLocation() {
        return $this->_location;
    }

    public function replaceLocation(iris\ILocationProvider $locationProvider) {
        $this->_location = $locationProvider->getLocation();
        return $this;
    }

    public function getLocationId() {
        return $this->getLine().':'.$this->getColumn();
    }


    public function duplicate(iris\ILocationProvider $locationProvider=null) {
        $output = clone $this;

        if($locationProvider) {
            $output->replaceLocation($locationProvider);
        }

        return $output;
    }

    public function normalize() {
        // do nothing by default
        return $this;
    }

// Comment
    public function setComment($comment) {
        $comment = trim($comment);

        if(empty($comment)) {
            $comment = null;
        }

        $this->_comment = $comment;
        return $this;
    }

    public function getComment() {
        return $this->_comment;
    }
}