<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\node;

use df;
use df\core;
use df\arch;
use df\link;

use DecodeLabs\Glitch;

abstract class RestApi extends Base implements IRestApiNode
{
    const DEFAULT_ACCESS = arch\IAccess::ALL;
    const OPTIMIZE = true;
    const CHECK_ACCESS = false;
    const CORS = false;
    const REGENERATE_ACCESS_TOKEN = false;

    protected $_httpRequest;

    public function dispatch()
    {
        $this->_httpRequest = $this->runner->getHttpRequest();
        return parent::dispatch();
    }

    public function getDispatchMethodName(): ?string
    {
        return '_handleRequest';
    }

    protected function _handleRequest()
    {
        $this->authorizeRequest();

        $httpMethod = $this->_httpRequest->getMethod();
        $func = 'execute'.ucfirst(strtolower($httpMethod)).'As'.$this->request->getType();

        if (!method_exists($this, $func)) {
            $func = 'execute'.ucfirst(strtolower($httpMethod));

            if (!method_exists($this, $func)) {
                throw Glitch::EApi([
                    'message' => 'Node does not support '.$httpMethod.' method',
                    'http' => 400
                ]);
            }
        }

        $response = $this->{$func}();

        return $this->_handleResponse($response);
    }

    protected function _handleResponse($response)
    {
        if ($response instanceof link\http\IResponse) {
            return $response;
        }

        if (!$response instanceof IRestApiResult) {
            $response = new arch\node\restApi\Result($response);
        }

        $response->setCors(static::CORS);

        if (
            static::REGENERATE_ACCESS_TOKEN &&
            !$response->hasException() &&
            !$response->hasAccessToken()
        ) {
            $response->setAccessToken(
                $this->regenerateAccessToken()
            );
        }

        return $response;
    }

    public function handleException(\Throwable $e)
    {
        core\log\Manager::getInstance()->logException($e);

        $data = null;

        if ($e instanceof \EGlitch) {
            $data = $e->getData();
        }

        $result = new arch\node\restApi\Result($data);
        $result->setException($e);

        return $this->_handleResponse($result);
    }

    public function newResult($value=null, core\validate\IHandler $validator=null)
    {
        return new arch\node\restApi\Result($value, $validator);
    }

    public function authorizeRequest()
    {
        return true;
    }

    public function regenerateAccessToken(): ?string
    {
        return null;
    }
}
