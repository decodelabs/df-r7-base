<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\unit;

use df;
use df\core;
    
class Resolution implements IResolution {

	use TSingleValueUnit;

	const DEFAULT_UNIT = 'dpi';

	private static $_units = ['dpi', 'dpcm', 'dppx'];

    protected $_value;
    protected $_unit;

    public static function factory($value, $unit=null) {
    	if($value instanceof IResolution) {
    		return $value;
    	}

    	return new self($value, $unit);
    }

	public function setDpi($dpi) {
		return $this->_parseUnit($dpi, 'dpi');
	}

	public function getDpi() {
		return $this->_convert($this->_value, $this->_unit, 'dpi');
	}

	public function setDpcm($dpcm) {
		return $this->_parseUnit($dpcm, 'dpcm');
	}

	public function getDpcm() {
		return $this->_convert($this->_value, $this->_unit, 'dpcm');
	}

	public function setDppx($dppx) {
		return $this->_parseUnit($dppx, 'dppx');
	}

	public function getDppx() {
		return $this->_convert($this->_value, $this->_unit, 'dppx');
	}

	protected function _convert($value, $inUnit, $outUnit) {
		if($inUnit == $outUnit) {
			return $value;
		}

		switch($inUnit) {
			case 'dpi':
				$dpi = $value;
				break;

			case 'dpcm':
				$dpi = $value * 2.54;
				break;

			case 'dppx':
				$dpi = $value * 96;
				break;
		}

		switch($outUnit) {
			case 'dpi':
				$value = $dpi;
				break;

			case 'dpcm':
				$value = $dpi / 2.54;
				break;

			case 'dppx':
				$value = $dpi / 96;
				break;
		}

		return $value;
	}
}