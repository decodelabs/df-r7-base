<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\image;

use df;
use df\core;
use df\neon;
    
class ImageMagick extends Base implements neon\IImageDrawingProcessor {

    public static function isLoadable() {
    	return extension_loaded('imagick');
    }

    public function canRead($type, $extension) {
        return true;
    }

    public function canWrite($type, $extension) {
        return true;
    }

    protected function _openFile() {
    	if(!$this->_sourcePath) {
    		return false;
    	}

    	try {
    		$this->_pointer = new \Imagick();
    		$this->_pointer->readImage($this->_sourcePath);
    	} catch(\ImagickException $e) {
    		return false;
    	}

    	$this->_width = $this->_pointer->getImageWidth();
    	$this->_height = $this->_pointer->getImageHeight();

    	return true;
    }

    protected function _openString($imageString) {
    	$this->_pointer = new \Imagick();

    	try {
    		$this->_pointer->readImageBlob($imageString);
    	} catch(\ImagickException $e) {
    		return false;
    	}

    	$this->_width = $this->_pointer->getImageWidth();
    	$this->_height = $this->_pointer->getImageHeight();

    	return true;
    }

    public function save($quality=100) {
    	$this->_prepareToSave($quality);
    	$output = $this->_pointer->writeImage($this->_targetPath);
    	$this->_destroy();

    	return $output;
    }

    public function toString($quality=100) {
		$this->_prepareToSave($quality);
		return $this->_pointer->getImageBlob();
    }

    protected function _prepareToSave($quality) {
    	$this->_normalizeSaveType();

    	$parts = explode('/', $this->_saveType);
    	$format = array_pop($parts);

    	$this->_pointer->setImageFormat($format);
    	$this->_pointer->setCompressionQuality($quality);
    }

    protected function _destroy() {
    	if($this->_pointer) {
    		$output = $this->_pointer->destroy();
    		$this->_pointer = null;

    		return $output;
    	}

    	return true;
    }

    protected function _createTempImage($fileName=null) {
    	if($fileName === null) {
            $fileName = $this->_createTempFileName();    
        }

        $this->_pointer->writeImage($fileName);
        return new self($fileName);
    }

    protected static function _createCanvas($width, $height, $color=null) {
    	$width = self::_normalizePixelSize($width);
        $height = self::_normalizePixelSize($height);

        $color = neon\Color::factory($color);
        $color->setMode('rgb');

        $output = new self();
        $output->_pointer = new \Imagick();
        $output->_pointer->newImage($width, $height, new \ImagickPixel($color->toCssString()));
        $output->_width = $width;
        $output->_height = $height;

        return $output;
    }

    public function copy(neon\IImage $image, $destX, $destY) {
    	$destX = self::_normalizePixelSize($destX);
        $destY = self::_normalizePixelSize($destY);

    	return $this->_pointer->compositeImage(
    		$image->_pointer,
    		\Imagick::COMPOSITE_DEFAULT,
    		$destX,
    		$destY
		);
    }


// Processors
	protected function _resize($width, $height) {
		$this->_pointer->resizeImage($width, $height, \Imagick::FILTER_LANCZOS, 1);
		$this->_width = $this->_pointer->getImageWidth();
    	$this->_height = $this->_pointer->getImageHeight();

		return $this;
	}

	public function rotate($angle, $background=null) {
		$angle = (int)$angle;

		if($angle % 360 == 0) {
            return $this;
        }

        if($background === null) {
        	$rColor = new \ImagickPixel('none');
        } else {
        	$background = neon\Color::factory($background);
        	$rColor = new \ImagickPixel($background->toCssString());
        }

        $this->_pointer->rotateImage($rColor, $angle);
        return $this;
	}

	public function crop($x, $y, $width, $height) {
		$x = self::_normalizePixelSize($x);
        $y = self::_normalizePixelSize($y);
        $width = self::_normalizePixelSize($width);
        $height = self::_normalizePixelSize($height);

        $this->_pointer->cropImage($width, $height, $x, $y);
        return $this;
	}

	public function mirror() {
		$this->_pointer->flopImage();
		return $this;
	}

	public function flip() {
		$this->_pointer->flipImage();
		return $this;
	}

	public function brightness($brightness) {
		$brightness += 100;
		$this->_pointer->modulateImage($brightness, 100, 100);
		return $this;
	}

	public function contrast($contrast) {
		$contrast = (int)$contrast / 10;

		while($contrast < 0) {
			$this->_pointer->contrastImage(0);
			$contrast++;
		}

		while($contrast > 0) {
			$this->_pointer->contrastImage(1);
			$contrast--;
		}

		return $this;
	}

	public function greyscale() {
		$this->_pointer->modulateImage(100, 0, 100);
		return $this;
	}

	public function colorize($color, $alpha=100) {
		$color = neon\Color::factory($color);
		$this->_pointer->colorizeImage($color->toCssString(), $alpha / 100);
		return $this;
	}

	public function invert() {
		$this->_pointer->negateImage(false);
		return $this;
	}

	public function detectEdges() {
		$this->_pointer->edgeImage(0);
		return $this;
	}

	public function emboss() {
		$this->_pointer->embossImage(0, 1);
		return $this;
	}

	public function blur() {
		$this->_pointer->blurImage(0, 1);
		return $this;
	}

	public function gaussianBlur() {
		$this->_pointer->gaussianBlurImage(0, 1);
		return $this;
	}

	public function removeMean() {
		$this->_pointer->unsharpMaskImage(0, 1, 8, 0.005);
		return $this;
	}

	public function smooth($amount=50) {
		$this->_pointer->blurImage(0, 0.5);
		return $this;
	}


// Drawing
	public function rectangleFill($x, $y, $width, $height, $color, $alpha=1) {
		core\stub();
	}

	public function gradientFill($orientation, $x, $y, $width, $height, array $colors) {
		core\stub();
	}
}