<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\restApi;

use df;
use df\core;
use df\arch;
use df\link;

abstract class Action extends arch\Action implements IAction {
    
    const DEFAULT_ACCESS = arch\IAccess::ALL;
    const OPTIMIZE = true;
    const CHECK_ACCESS = false;

    protected $_httpRequest;

    public function dispatch() {
        $this->_httpRequest = $this->application->getHttpRequest();
        return parent::dispatch();
    }

    public function getActionMethodName() {
        return '_handleRequest';
    }

    protected function _handleRequest() {
        if(method_exists($this->controller, 'authorizeRequest')) {
            $this->controller->authorizeRequest();
        }

        $this->authorizeRequest();

        $httpMethod = $this->_httpRequest->getMethod();
        $func = 'execute'.ucfirst(strtolower($httpMethod));
        $response = $this->{$func}();

        return $this->_handleResponse($response);
    }

    protected function _handleResponse($response) {
        if($response instanceof link\http\IResponse) {
            return $response;
        }

        if(!$response instanceof IResult) {
            $response = new Result($response);
        }

        return $response;
    }

    public function handleException(\Exception $e) {
        core\log\Manager::getInstance()->logException($e);

        $data = null;

        if($e instanceof core\ContextException) {
            $data = $e->data;
        }

        $result = new Result($data);
        $result->setException($e);

        return $this->_handleResponse($result);
    }

    public function authorizeRequest() {
        return true;
    }
}