<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\node\restApi;

use df;
use df\core;
use df\arch;
use df\link;
use df\flex;

class Result implements arch\node\IRestApiResult {

    public $value;
    public $validator;

    protected $_statusCode = null;
    protected $_exception;

    public function __construct($value=null, core\validate\IHandler $validator=null) {
        if(!$validator) {
            $validator = new core\validate\Handler();
        }

        $this->validator = $validator;
        $this->value = $value;
    }

    public function isValid() {
        if($this->_exception) {
            return false;
        }

        return $this->validator->isValid();
    }

    public function setStatusCode($code) {
        if(link\http\response\HeaderCollection::isValidStatusCode($code)) {
            $this->_statusCode = $code;
        } else {
            $this->_statusCode = null;
        }

        return $this;
    }

    public function getStatusCode() {
        if($this->_statusCode !== null) {
            return $this->_statusCode;
        }

        if($this->_exception instanceof core\IError) {
            $code = $this->_exception->getHttpCode();

            if(!link\http\response\HeaderCollection::isValidStatusCode($code)) {
                $code = 400;
            }

            return $code;
        }

        if($this->isValid()) {
            return 200;
        } else {
            return 400;
        }
    }

    public function setException(\Throwable $e) {
        $this->_exception = $e;
        return $this;
    }

    public function hasException() {
        return $this->_exception !== null;
    }

    public function getException() {
        return $this->_exception;
    }


// Response
    public function toResponse() {
        $isValid = $this->isValid();

        $data = [
            'success' => $isValid,
            'data' => $this->value
        ];

        if($this->_exception) {
            $data['error'] = [
                'message' => $this->_exception->getMessage(),
                'code' => $this->_exception->getCode(),
                'key' => null
            ];

            if($this->_exception instanceof core\IError) {
                $data['error']['key'] = $this->_exception->getKey();
            }
        }

        if(!$this->validator->isValid()) {
            $data['validation'] = $this->validator->data->toArrayDelimitedErrorSet();
        }

        $flags = 0;

        if(!df\Launchpad::isProduction()) {
            $flags = \JSON_PRETTY_PRINT;
        }
        
        $response = new link\http\response\Stream(
            flex\Json::toString($data, $flags),
            'application/json'
        );

        $response->getHeaders()->setStatusCode($this->getStatusCode());
        return $response;
    }
}
