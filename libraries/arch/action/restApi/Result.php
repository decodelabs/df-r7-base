<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\action\restApi;

use df;
use df\core;
use df\arch;
use df\link;
use df\flex;

class Result implements arch\action\IRestApiResult {

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

        if($this->_exception) {
            $code = $this->_exception->getCode();

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

    public function setException(\Exception $e) {
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
            $data['error'] = $this->_exception->getMessage();
            $data['code'] = $this->_exception->getCode();
        }

        if(!$this->validator->isValid()) {
            $data['validation'] = $this->validator->data->toArrayDelimitedErrorSet();
        }

        $response = new link\http\response\Stream(
            flex\json\Codec::encode($data),
            'application/json'
        );

        $response->getHeaders()->setStatusCode($this->getStatusCode());
        return $response;
    }
}