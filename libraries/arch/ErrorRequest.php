<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch;

use df;
use df\core;
use df\arch;

class ErrorRequest extends Request implements IErrorRequest {
    
    protected $_code;
    protected $_exception;
    protected $_lastRequest;
    
    public function __construct($code=500, \Exception $exception=null, IRequest $lastRequest=null) {
        if($code == 0) {
            $code = 500;
        }
        
        $this->_code = $code;
        $this->_exception = $exception;
        $this->_lastRequest = $lastRequest;
        
        if($lastRequest) {
            $area = $lastRequest->getArea();
        } else {
            $area = self::DEFAULT_AREA;
        }
        
        parent::__construct('error/');
    }
    
    public function getCode() {
        return $this->_code;
    }
    
    public function getException() {
        return $this->_exception;
    }
    
    public function getLastRequest() {
        return $this->_lastRequest;
    }
} 