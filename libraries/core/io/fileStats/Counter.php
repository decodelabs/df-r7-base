<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\io\fileStats;

use df;
use df\core;

class Counter {
    
    protected $_locations = array();
    
    public function __construct() {
        
    }
    
    public function addLocation(Location $location) {
        $this->_locations[$location->getId()] = $location;
        return $this;
    }

    public function getLocation($id) {
        if(isset($this->_locations[$id])) {
            return $this->_locations[$id];
        }
    }
    
    public function getLocations() {
        return $this->_locations;
    }
    
    public function run() {
        foreach($this->_locations as $location) {
            $location->run();
        }
        
        return $this;
    }
    
    public function countDirectories() {
        $output = 0;
        
        foreach($this->_locations as $location) {
            $output += $location->countDirectories();
        }
        
        return $output;
    }
    
    public function countFiles() {
        $output = 0;
        
        foreach($this->_locations as $location) {
            $output += $location->countFiles();
        }
        
        return $output;
    }
    
    public function countBytes() {
        $output = 0;
        
        foreach($this->_locations as $location) {
            $output += $location->countBytes();
        }
        
        return $output;
    }
    
    public function countLines() {
        $output = 0;
        
        foreach($this->_locations as $location) {
            $output += $location->countLines();
        }
        
        return $output;
    }
    
    public function getMergedLocation() {
        $output = new Location('Totals', null);
        
        foreach($this->_locations as $location) {
            $output->importLocation($location);
        }
        
        return $output;
    }
}