<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\svg\shape;

use df;
use df\core;
use df\neon;
    
class Image implements neon\svg\IImage, core\IDumpable {

	use TShape;
    use TShape_Primitive;
    use TShape_UrlAware;
    use TShape_DimensionAware;

    public function __construct($url, $width, $height, $position=null, $yPosition=null) {
    	$this->setUrl($url);
    	$this->setDimensions($width, $height);
    	$this->setPosition($position, $yPosition);
    }

// Dump
	public function getDumpProperties() {
		return array_merge(
            [
    			'url' => $this->_url,
				'dimensions' => $this->_width->toString().' x '.$this->_height->toString(),
				'position' => $this->_position->toString()
		    ],
            $this->_attributes
        );
	}
}