<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\image;

use df;
use df\core;
use df\neon;
    
abstract class Base implements neon\IImage, core\io\file\IPointer {

    private static $_driverClass;
    private static $_drivers = ['Gd'];

    protected static $_readTypes = array();
    protected static $_writeTypes = array();

    protected $_sourcePath;
    protected $_targetPath;

    protected $_pointer;

    protected $_width;
    protected $_height;

    protected $_saveType;

    public static function factory($sourcePath=null, $targetPath=null) {
    	$class = self::_getDriverClass();
    	return new $class($sourcePath, $targetPath);
    }

    public static function isLoadable() {
    	return false;
    }

    public static function canReadFile($path) {
    	if(!is_file($path)) {
    		return false;
    	}

    	return self::factory()->canRead(core\mime\Type::fileToMime($path));
    }

    public static function newCanvas($width, $height, $color=null) {
    	$class = self::getDriverClass();
    	return $class::_createCanvas($width, $height, $color)->convert('image/png');
    }

    protected static function _getDriverClass() {
    	if(self::$_driverClass === null) {
    		foreach(self::$_drivers as $name) {
    			$class = 'df\\neon\\image\\'.$name;

    			if(!class_exists($class) || !is_subclass_of($class, __CLASS__)) {
    				continue;
    			}

    			if($class::isLoadable()) {
    				self::$_driverClass = $class;
    				break;
    			}
    		}

    		if(self::$_driverClass === null) {
    			throw new neon\RuntimeException(
    				'No loadable driver was found to handle images'
				);
    		}
    	}

    	return self::$_driverClass;
    }


// Constructor
	public function __construct($sourcePath=null, $targetPath=null) {
		$this->setSourcePath($sourcePath);
		$this->setTargetPath($targetPath);
		$this->_open();
	}


// Paths
	public function setSourcePath($path) {
		if($path === null) {
			$this->_sourcePath = null;
			return $this;
		}

		if(!is_readable($path)) {
			throw new neon\InvalidArgumentException(
				'Source file '.$path.' is not readable'
			);
		}

		$p = pathinfo($path);
		$type = core\mime\Type::extToMime($p['extension']);

		if(!$this->canRead($type)) {
			throw new neon\RuntimeException(
				'This driver cannot read '.$type.' files'
			);
		}

		$this->_sourcePath = $path;
		return $this;
	}

	public function getSourcePath() {
		return $this->_sourcePath;
	}

	public function setTargetPath($path) {
		if($path === null) {
			$this->_targetPath = null;
			return $this;
		}

		if(!is_writable(dirname($path))) {
			throw new neon\InvalidArgumentException(
				'Target file '.$path.' is not writable'
			);
		}

		$p = pathinfo($path);
		$type = core\mime\Type::extToMime($p['extension']);

		if(!$this->canWrite($type)) {
			throw new neon\RuntimeException(
				'This driver cannot write '.$type.' files'
			);
		}

		$this->_targetPath = $path;
		return $this;
	}

	public function getTargetPath() {
		return $this->_targetPath;
	}


	public function isOpen() {
		return (bool)$this->_pointer;
	}


// Types
	public function canRead($type) {
		return in_array(strtolower($type), static::$_readTypes);
	}

	public function canWrite($type) {
		return in_array(strtolower($type), static::$_writeTypes);
	}

	public function getContentType() {
		if($this->_saveType) {
			return $this->_saveType;
		}


		if($this->_targetPath) {
			$p = pathinfo($this->_targetPath);
		} else {
			$p = pathinfo($this->_sourcePath);
		}

		return core\mime\Type::extToMime($p['extension'], 'image/png');
	}

	public function convertTo($type) {
		$type = strtolower($type);

		if(!$this->canWrite($type)) {
			throw new neon\RuntimeException(
				'This driver cannot write '.$type.' files'
			);
		}

		$this->_saveType = $type;
		return $this;
	}


	public function transform($str=null) {
		return new Transformation($this, $str);
	}


// Processors
	final public function resize($width, $height, $mode=neon\IImageProcessor::FIT) {
		if($width < 0) {
			$width = 0;
		}

		if($height < 0) {
            $height = 0;
        }
        
        if(!$width && !$height) {
            throw new neon\InvalidArgumentException(
            	'Invalid proportions specified for resize!'
        	);    
        }
        
        if(!$width || !$height) {
            $mode = neon\IImageProcessor::PROPORTIONAL;
            
            if(!$width) {
                $width = floor($this->_width * $height / $this->_height);    
            } 

            if(!$height) {
                $height = floor($this->_height * $width / $this->_width);    
            }
        }



        switch($mode) {
            case neon\IImageProcessor::STRETCH:
                $newWidth = $width;
                $newHeight = $height;
                break;
                
            case neon\IImageProcessor::FIT:
                if(($this->_height <= $height) && ($this->_width <= $width)) {
                    return;
                    $newWidth = $this->_width;
                    $newHeight = $this->_height;
                    break;    
                }
            
            default:
            case neon\IImageProcessor::PROPORTIONAL:
                $tmpWidth = $width;
                $tmpHeight = round($this->_height * $width / $this->_width);
                
                if($tmpHeight - $height > 1) {
                    $newHeight = $height;
                    $newWidth = round($this->_width * $height / $this->_height);
                } else {
                    $newWidth = $tmpWidth;
                    $newHeight = $tmpHeight;
                }
                break;
        }
        
        $this->_resize($newWidth, $newHeight);
                
        $this->_width = $newWidth;
        $this->_height = $newHeight;
        
        return $this;    
	}

	final public function cropZoom($width, $height) {
        if($width < 0) {
            $width = 0;
        }
        
        if($height < 0) {
            $height = 0;
        }
        
        if(!$width || !$height) {
            return;
        }

        $widthFactor = $width / $this->_width;
        $heightFactor = $height / $this->_height;

        if($widthFactor >= $heightFactor) {
            $this->resize($width, null);
            $x = 0;
            $y = round(($this->_height - $height) / 2);
        } else {
            $this->resize(null, $height);
            $y = 0;
            $x = round(($this->_width - $width) / 2);
        }
        
        return $this->crop($x, $y, $width, $height);    
    }

    final public function frame($width, $height, $color=null) {
        if($color !== null) {
            $color = neon\Color::factory($color);    
        }
        
        $this->resize($width, $height, neon\IImageProcessor::FIT);
        
        if(!$canvas = $this->_createCanvas($width, $height, $color)) {
            throw new neon\RuntimeException(
            	'Unable to create canvas!'
        	);    
        } 
        
        $canvas->copy($this, 
            round(($canvas->_width - $this->_width) / 2),
            round(($canvas->_height - $this->_height) / 2)
        );
        
        $this->_destroy();
        
        $this->_pointer = $canvas->_pointer;
        $this->_width = $canvas->_width;
        $this->_height = $canvas->_height;
        
        return $this;  
    }

    final public function watermark($image, $position=neon\IImageProcessor::BOTTOM_RIGHT, $scaleFactor=1.0) {
        if(!$image instanceof neon\IImage) {
            $image = self::factory($image);    
        }
        
        $tempImage = null;
        
        if($scaleFactor != 1.0 ||
         (($this->_width < $image->_width * $scaleFactor) || 
          ($this->_height < $image->_height * $scaleFactor))) {
            
            $tempImage = $image->_createTempImage();
            
            try {
                $tempImage->resize(
                    min((int)($image->_width * $scaleFactor), $this->_width),
                    min((int)($image->_height * $scaleFactor), $this->_height),
                    neon\IImageProcessor::PROPORTIONAL
                );    
                
                @unlink($tempImage->_sourcePath);
                
                $image = $tempImage;
            } catch(\Exception $e) {}    
        }  
        
        $copy = true;
        
        switch($position) {
            case neon\IImageProcessor::TILE:
                $watermarkX = 1;
                $watermarkY = 1;
                
                for($x = 0; $x < ceil($this->_width / $image->_width); $x++) {
                    for($y = 0; $y < ceil($this->_height / $image->_height); $y++) {
                        
                        if(!$x && !$y) {
                            continue;
                        }
                        
                        $this->copy(
                            $image,  
                            $watermarkX + $x * $image->_width,
                            $watermarkY + $y * $image->_height
                        );    
                    }    
                }
                
                $copy = false;
                
                break;
                
            case neon\IImageProcessor::TOP_LEFT:
                $watermarkX = 1;
                $watermarkY = 1;
                break;
                
            case neon\IImageProcessor::TOP_CENTER:
                $watermarkX = ($this->_width - $image->_width) / 2;
                $watermarkY = 1;
                break;
                
            case neon\IImageProcessor::TOP_RIGHT:
                $watermarkX = $this->_width - $image->_width;
                $watermarkY = 1;
                break;
                
            case neon\IImageProcessor::MIDDLE_LEFT:
                $watermarkX = 1;
                $watermarkY = ($this->_height - $image->_height) / 2;
                break;
                
            case neon\IImageProcessor::MIDDLE_CENTER:
                $watermarkX = ($this->_width - $image->_width) / 2;
                $watermarkY = ($this->_height - $image->_height) / 2;
                break;
                
            case neon\IImageProcessor::MIDDLE_RIGHT:
                $watermarkX = $this->_width - $image->_width;
                $watermarkY = ($this->_height - $image->_height) / 2;
                break;
                
            case neon\IImageProcessor::BOTTOM_LEFT:
                $watermarkX = 1;
                $watermarkY = $this->_height - $image->_height;
                break;
                
            case neon\IImageProcessor::BOTTOM_CENTER:
                $watermarkX = ($this->_width - $image->_width) / 2;
                $watermarkY = $this->_height - $image->_height;
                break;
                
            default:
            case neon\IImageProcessor::BOTTOM_RIGHT:
                $watermarkX = $this->_width - $image->_width;
                $watermarkY = $this->_height - $image->_height;
                break;
        } 
        
        if($copy) {
            $this->copy($image, $watermarkX, $watermarkY);
        }
        
        if($tempImage) {
            $tempImage->_destroy();    
        }
        
        return $this;               
    }



// Helpers
	protected static function _normalizePixelSize($size) {
        if(substr($size, -2) == 'px') {
            $size = substr($size, 0, -2);
        }
        
        if(!is_numeric($size)) {
            $size = preg_replace('[^0-9]', '', $size);
        }
        
        return (float)$size;
    }

    protected static function _normalizePercent($percent) {
        if(substr($percent, -1) == '%') {
            $percent = substr($percent, 0, -1) / 100;
        }
        
        return (float)$percent;
    }
    
    protected static function _normalizeGradientColorSet(array $colors, $gSize) {
        $colorSet = array();
        $lastStop = null;
        $colors = array_values($colors);
        
        foreach($colors as $i => $color) {
            if(is_array($color)) {
                $stop = array_pop($color);
                $color = neon\Color::factory(array_shift($color));
            } else {
                $color = neon\Color::factory($color);
                $stop = null;
            }
            
            if(strtolower(substr($stop, -2)) == 'px') {
                $stop = substr($stop, 0, -2) / $gSize;
            }
            
            if($stop !== null) {
                $stop = self::_normalizePercent($stop);
            }
            
            if($lastStop !== null && $stop < $lastStop) {
                $stop = null;
            }
            
            if($stop === null) {
                if($lastStop === null) {
                    $stop = 0;
                } else if(!isset($colors[$i + 1])) {
                    $stop = 1;
                } else {
                    $nextStop = null;
                    $skipCount = 1;
                    
                    for($j = $i; $j < count($colors); $j++) {
                        if(!is_array($colors[$j]) || !isset($colors[$j][1])) {
                            $skipCount++;
                            continue;
                        }
                        
                        $posStop = self::_normalizePercent($colors[$j][1]);
                        
                        if($posStop <= $lastStop) {
                            $skipCount++;
                            continue;
                        }
                        
                        $nextStop = $posStop;
                    }
                    
                    if($nextStop === null) {
                        $nextStop = 1;
                        $skipCount--;
                    }
                    
                    if($skipCount == 0) {
                        $stop = 1;
                    } else {
                        $range = $nextStop - $lastStop;
                        $stop = $lastStop + ($range / $skipCount);
                    }
                }
            }
            
            $colorSet[] = [$color, $stop];
            
            $lastStop = $stop;
        }
        
        if($colorSet[0][1] != 0) {
            array_unshift($colorSet, [clone $colorSet[0][0], 0]);
        }
        
        if($colorSet[count($colorSet) -1][1] != 1) {
            $colorSet[] = [clone $colorSet[count($colorSet) -1][0], 1];
        }
        
        return $colorSet;
    }



// IPointer
    public function openFile($mode=core\io\file\IMode::READ_WRITE) {
    	return new core\io\file\MemoryFileSystem(
    		$this->toString(),
    		$this->_sourcePath,
    		$this->getContentType(),
    		$mode
		);
    }

    public function exists() {
    	return true;
    }

    public function getSize() {
    	return strlen($this->toString());
    }

    public function getLastModified() {
    	return time();
    }

    public function getContents() {
    	return $this->toString();
    }

    public function putContents($data) {
    	throw new neon\LogicException(
    		'Cannot put image file contents - raw image data is read only'
		);
    }

    public function saveTo(core\uri\FilePath $path) {
    	if(!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        
        file_put_contents((string)$path, $this->toString());
        return $this;
    }


// Stubs
    abstract protected function _open();
    abstract protected static function _createCanvas($width, $height, $color=null);
    abstract protected function _resize($width, $height);
    abstract protected function _destroy();

    abstract protected function _createTempImage($fileName=null);

    protected function _createTempFileName() {
    	return tempnam(-1, null).'.png';
    }
}