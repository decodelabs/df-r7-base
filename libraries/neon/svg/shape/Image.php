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

    use neon\svg\TAttributeModule;
    use neon\svg\TAttributeModule_Shape;
    use neon\svg\TAttributeModule_Dimension;
    use neon\svg\TAttributeModule_Position;
    use neon\svg\TAttributeModule_XLink;

    public function __construct($href, $width, $height, $position=null, $yPosition=null) {
    	$this->setLinkHref($href);
    	$this->setDimensions($width, $height);
    	$this->setPosition($position, $yPosition);
    }

// Dump
	public function getDumpProperties() {
		return $this->_attributes;
	}
}