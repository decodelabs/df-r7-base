<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\raster;

use df;
use df\core;
use df\neon;
    
class Image implements IImage {

	private static $_driverClass;

	protected $_savePath;
	protected $_driver;

// Drivers
	public static function getDriverList() {
		return ['ImageMagick', 'Gd'];
	}

	public static function setDefaultDriver($driver) {
		if($driver instanceof IDriver) {
			self::$_driverClass = get_class($driver);
			return true;
		}

		$class = 'df\\neon\\raster\\driver\\'.$driver;

		if(class_exists($class)) {
			if(!$class::isLoadable()) {
				throw new RuntimeException(
					'Raster image driver '.$driver.' is not loadable'
				);
			}

			self::$_driverClass = $class;
			return true;
		}

		throw new RuntimeException(
			$driver.' is not a valid raster image driver'
		);
	}

	public static function getDefaultDriverClass() {
		if(!self::$_driverClass) {
			foreach(self::getDriverList() as $driver) {
				try {
					self::setDefaultDriver($driver);
				} catch(RuntimeException $e) {
					continue;
				}

				break;
			}

			if(!self::$_driverClass) {
				throw new RuntimeException(
					'There are no available raster image drivers'
				);
			}
		}

		return self::$_driverClass;
	}


// Loading
    public static function loadFile($file) {
    	$class = self::getDefaultDriverClass();

    	if(!is_readable($file)) {
    		throw new RuntimeException(
    			'Raster image '.$file.' is not readable'
			);
    	}

    	return new self((new $class())->loadFile($file));
    }

    public static function loadBase64String($string) {
    	return self::loadString(base64_decode($string));
    }

    public static function loadString($string) {
    	$class = self::getDefaultDriverClass();
    	
    	return new self((new $class())->loadString($string));
    }


    protected function __construct(IDriver $driver) {
    	$this->_driver = $driver;
    }


// Driver
	public function getDriver() {
		return $this->_driver;
	}
    


// Dimensions
	public function getWidth() {
		return $this->_driver->getWidth();
	}

	public function getHeight() {
		return $this->_driver->getHeight();
	}


// Transformation
	public function transform($string=null) {
		return new Transformation($this, $string);
	}


// Output format
	public function setOutputFormat($format) {
		if(!Format::isValid($format)) {
			throw new RuntimeException(
				$format.' is not a valid output raster image format'
			);
		}

		if(!$this->_driver->canWrite($format)) {
			throw new RuntimeException(
				$this->_driver->getName().' image driver cannot write '.$format.' format files'
			);
		}

		$this->_driver->setOutputFormat($format);
		return $this;
	}

	public function getOutputFormat() {
		return $this->_driver->getOutputFormat();
	}

// Save
	public function setSavePath($savePath) {
		if(!is_writable($savePath)) {
			throw new RuntimeException(
				'Raster image save path is not writable'
			);
		}

		$this->_savePath = $savePath;
		$this->setOutputFormat(Format::fromPath($savePath));

		return $this;
	}

	public function getSavePath() {
		return $this->_savePath;
	}

	public function saveTo($savePath, $quality=100) {
		$this->setSavePath($savePath);
		return $this->save($quality);
	}

	public function save($quality=100) {
		if(!$this->_savePath) {
			throw new RuntimeException(
				'Raster image save path has not been set'
			);
		}

		if(!$this->_driver->saveTo($savePath, $this->_normalizePercentage($quality))) {
			throw new RuntimeException(
				'Raster image could not be saved'
			);
		}

		return $this;
	}

	public function toString($quality=100) {
		return $this->_driver->toString($this->_normalizePercentage($quality));
	}



// Manipulations
	public function resize($width, $height, $mode=IDimension::PROPORTIONAL) {
		$this->_checkDriverForManipulations();

		$width = $this->_normalizePixelSize($width, IDimension::WIDTH);
		$height = $this->_normalizePixelSize($height, IDimension::HEIGHT);

		if(!$width && !$height) {
			throw new neon\raster\InvalidArgumentException(
				'Invalid proportions specified for resize'
			);
		}

		$currentWidth = $this->getWidth();
		$currentHeight = $this->getHeight();

		if(!$width || !$height) {
			if(!$width) {
				$width = floor($currentWidth * $height / $currentHeight);
			}

			if(!$height) {
				$height = floor($currentHeight * $width / $currentWidth);
			}
		}


		$mode = $this->_normalizeResizeMode($mode);

		switch($mode) {
			case IDimension::STRETCH:
				$newWidth = $width;
				$newHeight = $height;
				break;

			case IDimension::FIT:
				if($currentHeight <= $height && $currentWidth <= $width) {
					return $this;
				}

            case IDimension::PROPORTIONAL:
            	$newWidth = $width;
            	$newHeight = round($currentHeight * $width / $currentWidth);

            	if($newHeight - $height > 1) {
            		$newHeight = $height;
            		$newWidth = round($currentWidth * $height / $currentHeight);
            	}

            	break;
		}

		$this->_driver->resize($newWidth, $newHeight);
		return $this;
	}

	public function crop($x, $y, $width, $height) {
		$this->_checkDriverForManipulations();

		$x = $this->_normalizePixelSize($x, IDimension::WIDTH);
		$y = $this->_normalizePixelSize($y, IDimension::HEIGHT);
		$width = $this->_normalizePixelSize($width, IDimension::WIDTH);
		$height = $this->_normalizePixelSize($height, IDimension::HEIGHT);

		$this->_driver->crop($x, $y, $width, $height);
		return $this;
	}

	public function cropZoom($width, $height) {
		$this->_checkDriverForManipulations();

		$width = $this->_normalizePixelSize($width, IDimension::WIDTH);
		$height = $this->_normalizePixelSize($height, IDimension::HEIGHT);

		$currentWidth = $this->_driver->getWidth();
		$currentHeight = $this->_driver->getHeight();

		$widthFactor = $width / $currentWidth;
		$heightFactor = $height / $currentHeight;

		if($widthFactor >= $heightFactor) {
			$this->resize($width, null, IDimension::PROPORTIONAL);

			$x = 0;
			$y = round(($this->_driver->getHeight() - $height) / 2);
		} else {
			$this->resize(null, $height, IDimension::PROPORTIONAL);

			$x = round(($this->_driver->getWidth() - $width) / 2);
			$y = 0;
		}

		return $this->crop($x, $y, $width, $height);
	}

	public function frame($width, $height, $color=null) {
		$this->_checkDriverForManipulations();

		$width = $this->_normalizePixelSize($width, IDimension::WIDTH);
		$height = $this->_normalizePixelSize($height, IDimension::HEIGHT);
		$color = $this->_normalizeColor($color);

		$this->resize($width, $height, IDimension::FIT);

		$this->_driver = $this->_driver
			->spawnInstance()
			->loadCanvas($width, $height, $color)
			->composite(
				$this->_driver,
				round(($width - $this->_driver->getWidth()) / 2),
				round(($height - $this->_driver->getHeight()) / 2)
			);

		return $this;
	}

	public function rotate($angle, $background=null) {
		$this->_checkDriverForManipulations();

		$angle = $this->_normalizeAngle($angle);
		$background = $this->_normalizeColor($background);

		if($angle) {
			$this->_driver->rotate($angle, $background);
		}

		return $this;
	}

	public function mirror() {
		$this->_checkDriverForManipulations();
		$this->_driver->mirror();

		return $this;
	}

	public function flip() {
		$this->_checkDriverForManipulations();
		$this->_driver->flip();

		return $this;
	}



// Filters
	public function brightness($brightness) {
		$this->_checkDriverForFilters();

		$brightness = $this->_normalizePercentage($brightness, true, true);
		$this->_driver->brightness($brightness);

		return $this;
	}

	public function contrast($contrast) {
		$this->_checkDriverForFilters();

		$contrast = $this->_normalizePercentage($contrast, true, true);
		$this->_driver->contrast($contrast);

		return $this;
	}

	public function greyscale() {
		$this->_checkDriverForFilters();
		$this->_driver->greyscale();

		return $this;
	}

	public function colorize($color, $alpha=100) {
		$this->_checkDriverForFilters();

		$color = $this->_normalizeColor($color);
		$alpha = $this->_normalizePercentage($alpha);
		$this->_driver->colorize($color, $alpha);

		return $this;
	}

	public function invert() {
		$this->_checkDriverForFilters();
		$this->_driver->invert();

		return $this;
	}

	public function detectEdges() {
		$this->_checkDriverForFilters();
		$this->_driver->detectEdges();

		return $this;
	}

	public function emboss() {
		$this->_checkDriverForFilters();
		$this->_driver->emboss();

		return $this;
	}

	public function blur() {
		$this->_checkDriverForFilters();
		$this->_driver->blur();

		return $this;
	}

	public function gaussianBlur() {
		$this->_checkDriverForFilters();
		$this->_driver->gaussianBlur();

		return $this;
	}

	public function removeMean() {
		$this->_checkDriverForFilters();
		$this->_driver->removeMean();

		return $this;
	}

	public function smooth($amount=50) {
		$this->_checkDriverForFilters();
		
		$amount = $this->_normalizePercentage($amount);
		$this->_driver->smooth($amount);
		
		return $this;
	}


// Driver
	protected function _checkDriverForManipulations() {
		if(!$this->_driver instanceof IImageManipulationDriver) {
			throw new RuntimeException(
				'Raster image driver '.$this->_driver->getName().' does not support manipulations'
			);
		}
	}

	protected function _checkDriverForFilters() {
		if(!$this->_driver instanceof IImageFilterDriver) {
			throw new RuntimeException(
				'Raster image driver '.$this->_driver->getName().' does not support filters'
			);
		}
	}


// Normalizers
	protected function _normalizePixelSize($size, $dimension=null, $compositeSize=null) {
		if(is_string($size) && strlen($size) == 1) {
			$dimension = $this->_normalizeDimension($dimension);

			if($compositeSize !== null) {
				$compositeSize = $this->_normalizePixelSize($compositeSize);
			}

			switch($dimension) {
				case IDimension::WIDTH:
					switch($size) {
						case IPosition::LEFT:
							$size = 0;
							break; 

						case IPosition::CENTER:
							$size = $this->_driver->getWidth() / 2;

							if($compositeSize !== null) {
								$size -= $compositeSize / 2;
							}

							$size = floor($size);
							break;

						case IPosition::RIGHT:
							$size = $this->_driver->getWidth();

							if($compositeSize !== null) {
								$size -= $compositeSize;
							}

							break;
					}

					break;

				case IDimension::HEIGHT:
					switch($size) {
						case IPosition::TOP:
							$size = 0;
							break; 

						case IPosition::CENTER:
							$size = $this->_driver->getHeight() / 2;

							if($compositeSize !== null) {
								$size -= $compositeSize / 2;
							}

							$size = floor($size);
							break;

						case IPosition::BOTTOM:
							$size = $this->_driver->getHeight();

							if($compositeSize !== null) {
								$size -= $compositeSize;
							}

							break;
					}

					break;
			}
		}


		if(substr($size, -1) == '%') {
			$dimension = $this->_normalizeDimension($dimension);

			switch($dimension) {
				case IDimension::WIDTH:
					$base = $this->_driver->getWidth();
					break;

				case IDimension::HEIGHT:
					$base = $this->_driver->getHeight();
					break;
			}

			$percent = $this->_normalizePercentage($size);
			$size = ($base / 100) * $percent;
		}

        if(substr($size, -2) == 'px') {
            $size = substr($size, 0, -2);
        }
        
        if(!is_numeric($size)) {
            $size = preg_replace('[^0-9]', '', $size);
        }

        $size = (float)$size;

        if($size <= 0) {
        	$size = null;
        }
        
        return $size;
    }

	protected function _normalizePercentage($percent, $ignoreLowBound=false, $ignoreHighBound=false) {
		if(empty($percent)) {
			return null;
		}

		$percent = (float)trim($percent, '%');

		if(!$ignoreLowBound && $percent < 0) {
			$percent = 0;
		}

		if(!$ignoreHighBound && $percent > 100) {
			$percent = 100;
		}

		return $percent;
	}

	protected function _normalizeAngle($angle) {
		$angle = (int)$angle;

		while($angle > 360) {
			$angle -= 360;
		}

		while($angle < -360) {
			$angle += 360;
		}

		return $angle;
	}

	protected function _normalizeDimension($dimension) {
		switch($dimension) {
			case IDimension::HEIGHT:
				return IDimension::HEIGHT;

			case IDimension::WIDTH:
			default:
				return IDimension::WIDTH;
		}
	}

	protected function _normalizeResizeMode($mode) {
		switch($mode) {
			case IDimension::STRETCH:
				return IDimension::STRETCH;

			case IDimension::FIT:
				return IDimension::FIT;

            case IDimension::PROPORTIONAL:
            default:
            	return IDimension::PROPORTIONAL;
		}
	}

	protected function _normalizeColor($color, $default=null) {
		if(empty($color)) {
			if($default === null) {
				return null;
			}

			$color = $default;
		}

		return neon\Color::factory($color);
	}
}