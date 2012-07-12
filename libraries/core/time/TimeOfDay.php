<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\time;

use df;
use df\core;
    
class TimeOfDay implements ITimeOfDay, core\IDumpable {

	use core\TStringProvider;

    protected $_hours = 0;
    protected $_minutes = 0;
    protected $_seconds = 0;

    public static function factory($input) {
        if($input instanceof ITimeOfDay) {
            return $input;
        }

        return new self($input);
    }

    public function __construct($input) {
    	$this->_parseValue($input);
    }

    protected function _parseValue($input) {
    	if(is_int($input)) {
    		$input = [0, 0, $input];
    	}

    	if(is_string($input)) {
    		$input = explode(':', $input);
    	}

    	if(!is_array($input)) {
    		throw new InvalidArgumentException(
				'Value does not appear to be a valid time of day'
			);
    	}

    	if(isset($input[0])) {
			$this->_hours = (int)$input[0];
		}

		if(isset($input[1])) {
			$this->_minutes = (int)$input[1];
		}

		if(isset($input[2])) {
			$this->_seconds = (int)$input[2];
		}

		$this->_normalizeValues();
    }

    protected function _normalizeValues() {
    	while($this->_seconds < 0) {
    		$this->_seconds += 60;
    		$this->_minutes--;
    	}

    	if($this->_seconds >= 60) {
    		$this->_minutes += floor($this->_seconds / 60);
    		$this->_seconds %= 60;
    	}


    	while($this->_minutes < 0) {
    		$this->_minutes += 60;
    		$this->_hours--;
    	}

    	if($this->_minutes >= 60) {
    		$this->_hours += floor($this->_minutes / 60);
    		$this->_minutes %= 60;
    	}


    	while($this->_hours < 0) {
    		$this->_hours += 24;
    	}

    	if($this->_hours >= 24) {
    		$this->_hours %= 24;
    	}
    }

    public function toString() {
    	return sprintf('%02d:%02d:%02d', $this->_hours, $this->_minutes, $this->_seconds);
    }


// Seconds
    public function setSeconds($seconds) {
    	$this->_seconds = (int)$seconds;
    	$this->_normalizeValues();
    	return $this;
    }

    public function setAsSeconds($seconds) {
    	$this->_parseValue([0, 0, $seconds]);
    	return $this;
    }

    public function addSeconds($seconds) {
    	$this->_seconds += (int)$seconds;
    	$this->_normalizeValues();
    	return $this;
    }

    public function subtractSeconds($seconds) {
    	$this->_seconds -= (int)$seconds;
    	$this->_normalizeValues();
    	return $this;
    }

    public function getSeconds() {
    	return $this->_seconds;
    }

    public function getAsSeconds() {
    	return ($this->_hours * 3600) + ($this->_minutes * 60) + $this->_seconds;
    }


// Minutes
    public function setMinutes($minutes) {
    	$this->_minutes = (float)$minutes;
    	$this->_normalizeValues();
    	return $this;
    }

    public function setAsMinutes($minutes) {
    	$this->_parseValue([0, $minutes, 0]);
    	return $this;
    }

    public function addMinutes($minutes) {
    	$this->_minutes += (float)$minutes;
    	$this->_normalizeValues();
    	return $this;
    }

    public function subtractMinutes($minutes) {
    	$this->_minutes -= (float)$minutes;
    	$this->_normalizeValues();
    	return $this;
    }

    public function getMinutes() {
    	return $this->_minutes;
    }

    public function getAsMinutes() {
    	return ($this->_hours * 60) + $this->_minutes + ($this->_seconds / 60);
    }


// Hours
    public function setHours($hours) {
    	$this->_hours = (float)$hours;
    	$this->_normalizeValues();
    	return $this;
    }

    public function setAsHours($hours) {
    	$this->_parseValue([$hours, 0, 0]);
    	return $this;
    }

    public function addHours($hours) {
    	$this->_hours += (float)$hours;
    	$this->_normalizeValues();
    	return $this;
    }

    public function subtractHours($hours) {
    	$this->_hours -= (float)$hours;
    	$this->_normalizeValues();
    	return $this;
    }

    public function getHours() {
    	return $this->_hours;
    }

    public function getAsHours() {
    	return $this->_hours + ($this->_minutes / 60) + ($this->_seconds / 3600);
    }
    


// Dump
	public function getDumpProperties() {
		return $this->toString();
	}
}