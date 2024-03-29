<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\record\valueContainer;

use DecodeLabs\Glitch;
use df\core;

use df\opal;

class LazyLoad implements opal\record\IPreparedValueContainer
{
    use core\TUserValueContainer;

    protected $_value;
    protected $_isLoaded = false;
    protected $_loader;

    public function __construct($initValue, $loader)
    {
        $this->_value = $initValue;
        $this->_loader = core\lang\Callback::factory($loader);
    }

    public function isPrepared()
    {
        return $this->_isLoaded;
    }

    public function prepareValue(opal\record\IRecord $record, $fieldName)
    {
        $this->_value = $this->_loader->invoke($this->_value, $record, $fieldName);
        $this->_isLoaded = true;
        return $this;
    }

    public function prepareToSetValue(opal\record\IRecord $record, $fieldName)
    {
        return $this;
    }


    public function setValue($value)
    {
        $this->_value = $value;
        $this->_isLoaded = true;
        return $this;
    }

    public function getValue($default = null)
    {
        //if($this->_isLoaded) {
        return $this->_value;
        //}

        //Glitch::incomplete([$this->_value, $default]);
    }

    public function getValueForStorage()
    {
        return $this->_value;
    }


    public function duplicateForChangeList()
    {
        return clone $this;
    }

    public function eq($value)
    {
        return null;
    }

    public function getDumpValue()
    {
        return $this->_value;
    }
}
