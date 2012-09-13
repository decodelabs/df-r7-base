<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\image;

use df;
use df\core;
use df\neon;
    
class Gd extends Base implements neon\IImageDrawingProcessor {

    protected static $_readTypes = [
        // GIF
		'image/gif',
		
		// JPEG
		'image/jpg',
		'image/jpeg',
        'application/jpg',
        'application/x-jpg',
		
		// WBMP
		'image/wbmp',
		
		// XPM
		'image/x-xpixmap',
		'image/x-xpm',
		
		// XBM
		'image/x-xbitmap',
		'image/x-xbm',
		
		// PNG
		'image/png',
        'image/x-png',
        'application/png',
        'application/x-png'
    ];
    
    protected static $_writeTypes = [
        // GIF
		'image/gif',
		
		// JPEG
		'image/jpg',
		'image/jpeg',
        'application/jpg',
        'application/x-jpg',
		
		// WBMP
		'image/wbmp',
		
		// PNG
		'image/x-png',
		'image/png',
        'application/png',
        'application/x-png'
    ];


    public static function isLoadable() {
        return extension_loaded('gd');
    }

    public function canRead($type, $extension) {
        return in_array(strtolower($type), static::$_readTypes);
    }

    public function canWrite($type, $extension) {
        return in_array(strtolower($type), static::$_writeTypes);
    }

    protected function _openFile() {
    	if(!$this->_sourcePath) {
    		return false;
    	}

		@ini_set('memory_limit', -1);

		if($i = \getImageSize($this->_sourcePath)) {
            $this->_width = $i[0];
            $this->_height = $i[1];    
        }
        
        switch(@$i[2]) {
            case 1: // gif
                $this->_pointer = \imageCreateFromGif($this->_sourcePath);
                break;
                
            case 2: // jpg
                $this->_pointer = \imageCreateFromJpeg($this->_sourcePath);
                break;
                
            case 3: // png
                $this->_pointer = \imageCreateFromPng($this->_sourcePath);
                break;
                
            case 15: // wbmp
                $this->_pointer = \imageCreateFromWbmp($this->_sourcePath);
                break;
                
            case 16: // xbm
                $this->_pointer = \imageCreateFromXbm($this->_sourcePath);
                break;
                
            default:
                $this->_pointer = \imageCreateFromString(file_get_contents($this->_sourcePath));
        }
        
        return (bool)$this->_pointer;
    }

    protected function _openString($imageString) {
        if($i = \getImageSizeFromString($imageString)) {
            $this->_width = $i[0];
            $this->_height = $i[1];    
        }

        $this->_pointer = \imageCreateFromString($imageString);

        return (bool)$this->_pointer;
    }

    public function save($quality=100) {
        return $this->_save($quality, false);
    }
    
    public function toString($quality=100) {
        return $this->_save($quality, true);
    }
    
    protected function _save($quality, $toString=false) {
        $this->_normalizeSaveType(); 
        
        $output = false;
        ob_start();
        
        switch($this->_saveType) {
            case 'image/gif':
                imageTrueColorToPalette($this->_pointer, true, 256);
                $output = imageGif($this->_pointer);
                break;
                
            case 'image/jpeg':
                $output = imageJpeg($this->_pointer, null, $quality);
                break;
                
            case 'image/wbmp':
                $output = imageWbmp($this->_pointer);
                break;
                
            default:
            case 'image/png':
                //imageAlphaBlending($this->_pointer, false);
                imageSaveAlpha($this->_pointer, true);
                $output = imagePng($this->_pointer);
                break;    
        }
        
        if($toString) {
            return ob_get_clean();
        }
        
        $output = file_put_contents($this->_targetPath, ob_get_clean());
        
        $this->_destroy();
        
        return $output;
    }

     protected function _destroy() {
        if($this->_pointer) {
            return imageDestroy($this->_pointer);    
        }
        
        return true;
    }
    
    protected function _createTempImage($fileName=null) {
        if($fileName === null) {
            $fileName = $this->_createTempFileName();    
        }
        
        imageAlphaBlending($this->_pointer, false);
        imageSaveAlpha($this->_pointer, true);
        imagePng($this->_pointer, $fileName);
        
        return new self($fileName);
    }
    
    protected static function _createCanvas($width, $height, $color=null) {
        $width = self::_normalizePixelSize($width);
        $height = self::_normalizePixelSize($height);
        
        $output = new self();
        $output->_pointer = imageCreateTrueColor($width, $height);
        $output->_width = $width;
        $output->_height = $height;
        
        imageAlphaBlending($output->_pointer, false);
        imageSaveAlpha($output->_pointer, true);
        
        if($color === null) {
            $color = 'black';
        }
        
        $color = neon\Color::factory($color);
        $color->setMode('rgb');
        
        $alpha = $color->getAlpha();
        
        imageFill(
            $output->_pointer, 1, 1,
            imageColorAllocateAlpha(
                $output->_pointer,
                $color->red * 255,
                $color->green * 255,
                $color->blue * 255,
                127 - ($alpha * 127)
            ) 
        ); 
        
        return $output;
    }

    public function copy(neon\IImage $image, $destX, $destY) {
        $destX = self::_normalizePixelSize($destX);
        $destY = self::_normalizePixelSize($destY);

        imageAlphaBlending($this->_pointer, true);
        imageAlphaBlending($image->_pointer, true);
        
        $output = imageCopy(
            $this->_pointer, $image->_pointer,
            $destX, $destY, 0, 0,
            $image->_width, $image->_height
        );    
        
        imageAlphaBlending($this->_pointer, false);
        imageAlphaBlending($image->_pointer, false);
        
        return $output;
    }


// Processors
    protected function _resize($width, $height) {
        $img = imageCreateTrueColor($width, $height);
        
        $background = imageColorAllocateAlpha($img, 255, 255, 255, 127);
        imageColorTransparent($img, $background);
        
        imageAlphaBlending($img, false);
        imageSaveAlpha($img, true);
        imageFilledRectangle($img, 0, 0, $width, $height, $background);
        
        $output = imageCopyResampled(
            $img, $this->_pointer, 0, 0, 0, 0, $width, $height, $this->_width, $this->_height
        );
        
        $this->_destroy();
        $this->_pointer = $img;
        
        return $this;     
    }
    
    public function rotate($angle, $background=null) {
        $angle = (int)$angle;

        if($angle % 360 == 0) {
            return $this;
        }    
        
        if($background === null) {
            $rColor = -1;    
        } else {
            $background = neon\Color::factory($background);
            
            $rColor = imageColorAllocate(
                $this->_pointer,
                $background->red * 255, 
                $background->green * 255, 
                $background->blue * 255
            );    
        }
        
        if(!($tmp = imageRotate($this->_pointer, $angle * -1, $rColor))) {
            return $this;    
        }
        
        $this->_destroy();
        
        $this->_pointer = $tmp;
        $this->_width = imageSX($tmp);
        $this->_height = imageSX($tmp);
        
        return $this;
    }
    
    public function crop($x, $y, $width, $height) {
        $x = self::_normalizePixelSize($x);
        $y = self::_normalizePixelSize($y);
        $width = self::_normalizePixelSize($width);
        $height = self::_normalizePixelSize($height);

        $img = imageCreateTrueColor($width, $height);
        
        $background = imageColorAllocateAlpha($img, 255, 255, 255, 127);
        imageColorTransparent($img, $background);
        
        imageAlphaBlending($img, false);
        imageSaveAlpha($img, true);
        imageFilledRectangle($img, 0, 0, $width, $height, $background);
        
        
        $output = imageCopy($img, $this->_pointer, 0, 0, $x, $y, $width, $height);
        imageAlphaBlending($img, false);
        
        $this->_destroy();
        
        $this->_pointer = $img;
        $this->_width = $width;
        $this->_height = $height;
        
        return $this;    
    }
    
    public function mirror() {
        $tmp = imageCreateTrueColor($this->_width, $this->_height);
        imageAlphaBlending($tmp, true);
        
        for($x = 0; $x < $this->_width; $x++) {
            imageCopy(
                $tmp, $this->_pointer, $x, 0,
                $this->_width - $x - 1, 0,
                1, $this->_height
            );    
        }    
        
        imageAlphaBlending($tmp, false);
        
        $this->_destroy();
        $this->_pointer = $tmp;
        
        return $this;
    }
    
    public function flip() {
        $tmp = imageCreateTrueColor($this->_width, $this->_height);
        imageAlphaBlending($tmp, true);
        
        for($y = 0; $y < $this->_height; $y++) {
            imageCopy(
                $tmp, $this->_pointer, 0, $y,
                0, $this->_height - $y - 1,
                $this->_width, 1
            );    
        }    
        
        imageAlphaBlending($tmp, false);
        
        $this->_destroy();
        $this->_pointer = $tmp;
        
        return $this;
    }
    
    public function brightness($brightness) {
        imageAlphaBlending($this->_pointer, false);
        imageSaveAlpha($this->_pointer, true);
        
        if(function_exists('imagefilter')) {
            imagefilter($this->_pointer, \IMG_FILTER_BRIGHTNESS, $brightness);
        }    
        
        return $this;
    }
    
    public function contrast($contrast) {
        imageAlphaBlending($this->_pointer, false);
        imageSaveAlpha($this->_pointer, true);

        $contrast *= -1;
        
        if(function_exists('imagefilter')) {
            imagefilter($this->_pointer, \IMG_FILTER_CONTRAST, $contrast);
        }    
        
        return $this;
    }
    
    public function greyscale() {
        imageAlphaBlending($this->_pointer, false);
        imageSaveAlpha($this->_pointer, true);
        
        if(function_exists('imagefilter')) {
            imagefilter($this->_pointer, \IMG_FILTER_GRAYSCALE);
        } else {
            imageCopyMergeGray(
                $this->_pointer, $this->_pointer,
                0, 0, 0, 0,
                $this->_width, $this->_height, 0
            ); 
        }   
        
        return $this;
    }
    
    public function colorize($color, $alpha=100) {
        imageAlphaBlending($this->_pointer, false);
        imageSaveAlpha($this->_pointer, true);
        
        if(function_exists('imagefilter')) {
            $color = neon\Color::factory($color);
            imagefilter(
                $this->_pointer, 
                \IMG_FILTER_COLORIZE, 
                $color->red * 255, 
                $color->green * 255, 
                $color->blue * 255, 
                $alpha / 100 * -127
            );
        }    
        
        return $this;
    }
    
    public function invert() {
        imageAlphaBlending($this->_pointer, false);
        imageSaveAlpha($this->_pointer, true);
        
        if(function_exists('imagefilter')) {
            imagefilter($this->_pointer, \IMG_FILTER_NEGATE);
        }    
        
        return $this;     
    }
    
    public function detectEdges() {
        imageAlphaBlending($this->_pointer, false);
        imageSaveAlpha($this->_pointer, true);
        
        if(function_exists('imagefilter')) {
            imagefilter($this->_pointer, \IMG_FILTER_EDGEDETECT);
        }    
        
        return $this;     
    }
    
    public function emboss() {
        imageAlphaBlending($this->_pointer, false);
        imageSaveAlpha($this->_pointer, true);
        
        if(function_exists('imagefilter')) {
            imagefilter($this->_pointer, \IMG_FILTER_EMBOSS);
        }    
        
        return $this;   
    }
    
    public function blur() {
        imageAlphaBlending($this->_pointer, false);
        imageSaveAlpha($this->_pointer, true);
        
        if(function_exists('imagefilter')) {
            imagefilter($this->_pointer, \IMG_FILTER_SELECTIVE_BLUR);
        }    
        
        return $this;   
    }
    
    public function gaussianBlur() {
        imageAlphaBlending($this->_pointer, false);
        imageSaveAlpha($this->_pointer, true);
        
        if(function_exists('imagefilter')) {
            imagefilter($this->_pointer, \IMG_FILTER_GAUSSIAN_BLUR);
        }    
        
        return $this;   
    }
    
    public function removeMean() {
        imageAlphaBlending($this->_pointer, false);
        imageSaveAlpha($this->_pointer, true);
        
        if(function_exists('imagefilter')) {
            imagefilter($this->_pointer, \IMG_FILTER_MEAN_REMOVAL);
        }    
        
        return $this;   
    }
    
    public function smooth($amount=50) {
        imageAlphaBlending($this->_pointer, false);
        imageSaveAlpha($this->_pointer, true);
        
        if(function_exists('imagefilter')) {
            imagefilter($this->_pointer, \IMG_FILTER_SMOOTH, $amount);
        }    
        
        return $this;   
    }


// Drawing
	public function rectangleFill($x, $y, $width, $height, $color, $alpha=1) {
        $color = neon\Color::factory($color);
        
        imageFilledRectangle(
            $this->_pointer,
            (int)$x, (int)$y,
            (int)($x + $width), (int)($y + $height), 
            imageColorAllocateAlpha(
                $this->_pointer,
                $color->red,
                $color->green,
                $color->blue,
                127 - ($alpha * 127)
            ) 
        ); 
        
        return $this;
    }
    
    public function gradientFill($orientation, $x, $y, $width, $height, array $colors) {
        $x = $this->_normalizePixelSize($x);
        $y = $this->_normalizePixelSize($y);
        $width = $this->_normalizePixelSize($width);
        $height = $this->_normalizePixelSize($height);
        
        $flip = false;
        $rotate = false;
                
        switch(strtolower($orientation)) {
            case 'right':
                $flip = true;
            case 'left':
                $totalRows = $width;
                $rotate = true;
                break;

            case 'bottom':
                $flip = true;
            case 'top':
            default:
                $totalRows = $height;
                break;
        }
        
        $cols = $rotate ? $height : $width;
        $rangeList = array();
        
        $colors = $this->_normalizeGradientColorSet($colors, $totalRows);
        
        foreach($colors as $color) {
            $range = floor($totalRows * $color[1]);
            
            if($flip) {
                $range = $totalRows - $range;
            }
            
            while(isset($rangeList["$range"])) {
                $range += 0.0001;
            }
            
            $rangeList["$range"] = $color[0];
        }
        
        ksort($rangeList);
        
        $startColor = $rangeList[0];
        unset($rangeList[0]);
        $start = 0;
        
        
        foreach($rangeList as $end => $endColor) {
            $test[] = [
                'start' => $start,
                'end' => $end,
                'startColor' => $startColor,
                'endColor' => $endColor
            ];
            
            $rows = $end - $start;

            $stepColor = new neon\Color(
                ($endColor->getRed() - $startColor->getRed()) / $rows,
                ($endColor->getGreen() - $startColor->getGreen()) / $rows,
                ($endColor->getBlue() - $startColor->getBlue()) / $rows
            );
            
            
            $activeColor = clone $startColor;
            $alphaStep = ((127 * $endColor->getAlpha()) - (127 * $startColor->getAlpha())) / $rows;
            $activeAlpha = $startColor->getAlpha() * 127;
            
            
            for($j = 0; $j < $rows; $j++) {
                $activeColor->add($stepColor);
                $activeAlpha += $alphaStep;
                
                $colorAllocate = imageColorAllocateAlpha(
                    $this->_pointer,
                    round($activeColor->getRed() * 255),
                    round($activeColor->getGreen() * 255),
                    round($activeColor->getBlue() * 255),
                    127 - $activeAlpha
                );
                
                for($k = 0; $k < $cols; $k++) {
                    $currentX = $x + ($rotate ? $j : $k);
                    $currentY = $y + ($rotate ? $k : $j);
                    
                    imageSetPixel(
                        $this->_pointer,
                        $currentX, $currentY,
                        $colorAllocate
                    );
                }
            }
            
            if($rotate) {
                $x += $rows;
            } else {
                $y += $rows;
            }
            
            $start = $end;
            $startColor = $endColor;
        }
        
        return $this;
    }
}