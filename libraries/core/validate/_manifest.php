<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate;

use df;
use df\core;


// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}



// Interfaces
interface IHandler {
    public function addField($name, $type, array $options=null);
    public function getField($name);
    public function getFields();
    public function getValues();
    public function getValue($name);
    public function shouldSanitize($flag=null);
    
    public function isValid();
    public function validate(core\collection\IInputTree $data);
    public function applyTo(&$targetRecord);
}



interface IField {
    public function getHandler();
    public function getName();
    public function isRequired($flag=null);
    public function shouldSanitize($flag=null);
    public function setCustomValidator(\Closure $validator);
    public function getCustomValidator();
    
    public function end();
    public function validate(core\collection\IInputTree $node);
    public function applyValueTo(&$record, $value);
}


interface ITextField extends IField {
    public function setSanitizer(\Closure $sanitizer);
    public function getSanitizer();
    
    public function setPattern($pattern);
    public function getPattern();
    
    public function setMinLength($length);
    public function getMinLength();
    
    public function setMaxLength($length);
    public function getMaxLength();
}
