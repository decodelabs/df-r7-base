<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\style;

use df;
use df\core;
use df\aura;
    
class Position implements IPosition, core\IDumpable {

	use core\TStringProvider;

    protected $_xAnchor = null;
    protected $_xOffset;
    protected $_yAnchor = null;
    protected $_yOffset;

    public function factory($position, $position2=null) {
    	if($position instanceof IPosition) {
    		return $position;
    	}

    	return new self($position, $position2);
    }

    public function __construct($position, $position2=null) {
    	$this->parse($position, $position2);
    }

    public function parse($position, $position2=null) {
    	if($position2 !== null) {
    		$this->setX($position);
			$this->setY($position2);

			return $this;
		}


		$parts = explode(' ', strtolower($position));

		while(!empty($parts)) {
			$part = array_shift($parts);

			switch($part) {
				case 'top':
				case 'bottom':
					$this->setYAnchor($part);

					if(isset($parts[0]) 
					&& !in_array($parts[0], ['left', 'right', 'center'])
					&& (isset($parts[1]) || $this->_xAnchor !== null)) {
						$this->setYOffset(array_shift($parts));
					}

					break;

				case 'left':
				case 'right':
					$this->setXAnchor($part);

					if(isset($parts[0]) 
					&& !in_array($parts[0], ['top', 'bottom', 'center'])
					&& (isset($parts[1]) || $this->_yAnchor !== null)) {
						$this->setXOffset(array_shift($parts));
					}

					break;

				case 'center':
					if($this->_xAnchor === null) {
						$this->setXAnchor($part);
					} else {
						$this->setYAnchor($part);
					}

					break;

				default:
					if($this->_xOffset === null) {
						$this->setXOffset($part);

						if($this->_xAnchor === null) {
							$this->setXAnchor('left');
						}
					} else {
						$this->setYOffset($part);

						if($this->_yAnchor === null) {
							$this->setYAnchor('top');
						}
					}

					break;
			}
    	}

    	if($this->_yAnchor === null) {
    		$this->setYAnchor('center');
    	}

    	return $this;
    }

    public function setX($value) {
    	$parts = explode(' ', strtolower($value), 2);

    	if(isset($parts[1])) {
    		$this->setXOffset(array_pop($parts));
    		$this->setXAnchor(array_shift($parts));
    	} else if(in_array($value, ['left', 'right', 'center'])) {
    		$this->setXAnchor($value);
    	} else {
    		$this->setXOffset($value);
    		$this->setXAnchor('left');
    	}
    	
    	return $this;
    }

	public function getX() {
		$output = $this->_xAnchor;

		if($this->_xOffset !== null) {
			$output .= ' '.$this->_xOffset;
		}

		return $output;
	}

	public function setXAnchor($anchor) {
		$anchor = strtolower($anchor);

		switch($anchor) {
			case 'left':
			case 'center':
			case 'right':
				break;

			default:
				$anchor = 'left';
				break;
		}

		$this->_xAnchor = $anchor;
		return $this;
	}

	public function getXAnchor() {
		return $this->_xAnchor;
	}

	public function setXOffset($offset) {
		if($offset !== null) {
			$offset = Size::factory($offset);
		}

		$this->_xOffset = $offset;
		return $this;
	}

	public function getXOffset() {
		return $this->_xOffset;
	}

	public function setY($value) {
		$parts = explode(' ', strtolower($value), 2);

    	if(isset($parts[1])) {
    		$this->setYOffset(array_pop($parts));
    		$this->setYAnchor(array_shift($parts));
    	} else if(in_array($value, ['top', 'bottom', 'center'])) {
    		$this->setYAnchor($value);
    	} else {
    		$this->setYOffset($value);
    		$this->setYAnchor('top');
    	}
    	
    	return $this;
	}

	public function getY() {
		$output = $this->_yAnchor;

		if($this->_yOffset !== null) {
			$output .= ' '.$this->_yOffset;
		}

		return $output;
	}

	public function setYAnchor($anchor) {
		$anchor = strtolower($anchor);

		switch($anchor) {
			case 'top':
			case 'bottom':
			case 'center':
				break;

			default:
				$anchor = 'top';
				break;
		}

		$this->_yAnchor = $anchor;
		return $this;
	}
	
	public function getYAnchor() {
		return $this->_yAnchor;
	}

	public function setYOffset($offset) {
		if($offset !== null) {
			$offset = Size::factory($offset);
		}

		$this->_yOffset = $offset;
		return $this;
	}

	public function getYOffset() {
		return $this->_yOffset;
	}

	public function toString() {
		return $this->getX().' '.$this->getY();
	}


// Dump
	public function getDumpProperties() {
		return $this->toString();
	}
}