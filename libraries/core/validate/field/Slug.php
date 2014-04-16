<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;

class Slug extends Base implements core\validate\ISlugField {

    use core\validate\TStorageAwareField;
    use core\validate\TSanitizingField;
    use core\validate\TUniqueCheckerField;

    protected $_allowPathFormat = false;
    protected $_allowAreaMarker = false;
    protected $_defaultValueField = null;
    protected $_generateIfEmpty = false;

    public function allowPathFormat($flag=null) {
        if($flag !== null) {
            $this->_allowPathFormat = (bool)$flag;
            return $this;    
        }    
        
        return $this->_allowPathFormat;
    }

    public function allowAreaMarker($flag=null) {
        if($flag !== null) {
            $this->_allowAreaMarker = (bool)$flag;
            return $this;
        }

        return $this->_allowAreaMarker;
    }

    public function setDefaultValueField($field) {
        $this->_defaultValueField = $field;
        return $this;    
    }
    
    public function getDefaultValueField() {
        return $this->_defaultValueField;    
    }

    public function shouldGenerateIfEmpty($flag=null) {
        if($flag !== null) {
            $this->_generateIfEmpty = (bool)$flag;
            return $this;    
        }   
        
        return $this->_generateIfEmpty;
    }

    

    public function validate(core\collection\IInputTree $node) {
        $value = $node->getValue();
        $value = $this->_sanitizeValue($value, false);
        $value = $this->_sanitizeSlugValue($value);
        $value = $this->_sanitizeValue($value, true);

        if(false !== strpos($value, '/') && !$this->_allowPathFormat) {
            $this->_applyMessage($node, 'invalid', $this->_handler->_(
                'Path type slugs are not allowed here'
            ));
        }

        if($this->_allowPathFormat && substr($value, -1) == '/') {
            $this->_applyMessage($node, 'required', $this->_handler->_(
                'You must enter a full path slug'
            ));

            return null;
        }
        
        
        if(!$length = $this->_checkRequired($node, $value)) {
            return null;
        }

        $this->_validateUnique($node, $value);
        return $this->_finalize($node, $value);
    }

    protected function _sanitizeSlugValue($value) {
        $value = trim($value);

        if(empty($value) && $this->_defaultValueField) {
            $data = $this->_handler->getCurrentData();

            if($data->has($this->_defaultValueField)) {
                $value = trim($data[$this->_defaultValueField]);
            }
        }

        if(empty($value) && $this->_generateIfEmpty) {
            $value = core\string\Generator::random();
        }

        if($this->_allowPathFormat) {
            $value = core\string\Manipulator::formatPathSlug($value, $this->_allowAreaMarker ? '~' : null);
        } else {
            $value = core\string\Manipulator::formatSlug($value);
        }

        return $value;
    }
}