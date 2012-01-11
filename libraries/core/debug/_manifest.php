<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug;

use df;
use df\core;

// Interfaces
interface INode {
    
}




interface IStackTrace {
    
}

interface IStackCall {
    public function getArgs();
    public function hasArgs();
    public function countArgs();
    
    public function getType();
    public function isStatic();
    public function isObject();
    public function isNamespaceFunction();
    public function isGlobalFunction();
    
    public function getNamespace();
    public function hasNamespace();
    
    public function getClass();
    public function hasClass();
    public function getClassName();
    
    public function getFunctionName();
    public function getCallSignature();
    
    public function getCallingFile();
    public function getCallingLine();
    public function getOriginFile();
    public function getOriginLine();
}
