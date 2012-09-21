<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\svg\shape;

use df;
use df\core;
use df\neon;


trait TShape {

	use neon\svg\TElement;
	use neon\svg\TAttributeModule;
	use neon\svg\TAttributeModule_Clip;
	use neon\svg\TAttributeModule_Conditional;
	use neon\svg\TAttributeModule_Container;
	use neon\svg\TAttributeModule_Core;
	use neon\svg\TAttributeModule_Cursor;
	use neon\svg\TAttributeModule_ExternalResources;
	use neon\svg\TAttributeModule_Filter;
	use neon\svg\TAttributeModule_FilterColor;
	use neon\svg\TAttributeModule_Flood;
	use neon\svg\TAttributeModule_Font;
	use neon\svg\TAttributeModule_Graphics;
	use neon\svg\TAttributeModule_GraphicalElementEvents;
	use neon\svg\TAttributeModule_Gradient;
	use neon\svg\TAttributeModule_Marker;
	use neon\svg\TAttributeModule_Mask;
	use neon\svg\TAttributeModule_Paint;
	use neon\svg\TAttributeModule_PaintOpacity;
    use neon\svg\TAttributeModule_Style;
    use neon\svg\TAttributeModule_Text;
    use neon\svg\TAttributeModule_TextContent;
    use neon\svg\TAttributeModule_Transform;
    use neon\svg\TAttributeModule_Viewport;
}    


trait TShape_Primitive {

	protected $_position;

	public function setPosition($position, $yPosition=null) {
		$this->_position = core\unit\DisplayPosition::factory($position, $yPosition);
		return $this;
	}

	public function getPosition() {
		return $this->_position;
	}
}

trait TShape_PointData {

	protected $_points = array();

	public function setPoints($points) {
		if(is_string($points)) {
			$points = explode(' ', $points);
		}

		if(!is_array($points)) {
			$points = array($points);
		}

		if(count($points) < self::MIN_POINTS) {
			throw new InvalidArgumentException(
				$this->getName().' shape elements require at least '.self::MIN_POINTS.' points'
			);
		}

		if(self::MAX_POINTS !== null && count($points) > self::MAX_POINTS) {
			throw new InvalidArgumentException(
				$this->getName().' shape elements require no more than '.self::MAX_POINTS.' points'
			);
		}

		foreach($points as $i => $point) {
			if(is_string($point)) {
				if(false !== strpos($point, ',')) {
					$point = explode(',', trim($point));
				} else {
					$point = core\unit\DisplayPosition::factory($point);
				}
			}

			if(is_array($point)) {
				$point = core\unit\DisplayPosition::factory(array_shift($point), array_shift($point));
			}

			if(!$point instanceof core\unit\IDisplayPosition) {
				throw new InvalidArgumentException(
					'Invalid point detected in '.$this->getName()
				);
			}

			$points[$i] = $point;
		}

		$this->_points = $points;
		return $this;
	}

	public function getPoints() {
		return $this->_points;
	}
}

trait TShape_RadiusAware {

	protected $_radius;

	public function setRadius($radius) {
		$this->_radius = core\unit\DisplaySize::factory($radius);
		return $this;
	}

	public function getRadius() {
		return $this->_radius;
	}
}


trait TShape_2DRadiusAware {

	protected $_xRadius;
	protected $_yRadius;

	public function setRadius($radius) {
		$this->setXRadius($radius);
		$this->setYRadius($radius);
	}

	public function setXRadius($radius) {
		$this->_xRadius = core\unit\DisplaySize::factory($radius);
		return $this;
	}

	public function getXRadius() {
		return $this->_xRadius;
	}

	public function setYRadius($radius) {
		$this->_yRadius = core\unit\DisplaySize::factory($radius);
		return $this;
	}

	public function getYRadius() {
		return $this->_yRadius;
	}
}


trait TShape_DimensionAware {

	protected $_width;
	protected $_height;

	public function setDimensions($width, $height) {
		return $this->setWidth($width)->setHeight($height);
	}

	public function setWidth($width) {
		$this->_width = core\unit\DisplaySize::factory($width);
		return $this;
	}

	public function getWidth() {
		return $this->_width;
	}

	public function setHeight($height) {
		$this->_height = core\unit\DisplaySize::factory($height);
		return $this;
	}

	public function getHeight() {
		return $this->_height;
	}
}


trait TShape_UrlAware {

	protected $_url;

	public function setUrl($url) {
		$this->_url = $url;
		return $this;
	}

	public function getUrl() {
		return $this->_url;
	}
}