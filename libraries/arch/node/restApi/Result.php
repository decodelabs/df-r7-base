<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\arch\node\restApi;

use DecodeLabs\Exceptional;
use DecodeLabs\Genesis;
use DecodeLabs\Glitch;
use df\arch;

use df\core;
use df\flex;
use df\link;

class Result implements arch\node\IRestApiResult
{
    public $value;
    public $validator;

    protected $_statusCode = null;
    protected $_cors = null;

    protected $_exception;
    protected $_dataProcessor;

    protected $_accessToken;
    protected $_refreshToken;


    public function __construct($value = null, core\validate\IHandler $validator = null)
    {
        if (!$validator) {
            $validator = new core\validate\Handler();
        }

        $this->validator = $validator;
        $this->value = $value;
    }

    public function isValid(): bool
    {
        if ($this->_exception) {
            return false;
        }

        return $this->validator->isValid();
    }

    public function setStatusCode(?int $code)
    {
        if (link\http\response\HeaderCollection::isValidStatusCode($code)) {
            $this->_statusCode = $code;
        } else {
            $this->_statusCode = null;
        }

        return $this;
    }

    public function getStatusCode(): int
    {
        if ($this->_statusCode !== null) {
            return $this->_statusCode;
        }

        if ($this->_exception instanceof Exceptional\Exception) {
            $code = $this->_exception->getHttpStatus();

            if (!link\http\response\HeaderCollection::isValidStatusCode($code)) {
                $code = 400;
            }

            return (int)$code;
        }

        if ($this->isValid()) {
            return 200;
        } else {
            return 400;
        }
    }

    public function setException(\Throwable $e)
    {
        $this->_exception = $e;
        return $this;
    }

    public function hasException(): bool
    {
        return $this->_exception !== null;
    }

    public function getException(): ?\Throwable
    {
        return $this->_exception;
    }

    public function complete(callable $success, callable $failure = null)
    {
        if ($this->isValid()) {
            try {
                $this->value = $success($this->validator);
            } catch (\Throwable $e) {
                $this->_exception = $e;
            }
        } elseif ($failure) {
            try {
                $failure($this->validator);
            } catch (\Throwable $e) {
                $this->_exception = $e;
            }
        }

        return $this;
    }



    public function setDataProcessor(?callable $processor)
    {
        $this->_dataProcessor = $processor;
        return $this;
    }

    public function getDataProcessor(): ?callable
    {
        return $this->_dataProcessor;
    }

    public function setCors(?string $cors)
    {
        $this->_cors = $cors;
        return $this;
    }

    public function getCors(): ?string
    {
        return $this->_cors;
    }



    // Tokens
    public function setAccessToken(?string $token)
    {
        $this->_accessToken = $token;
        return $this;
    }

    public function hasAccessToken(): bool
    {
        return $this->_accessToken !== null;
    }

    public function getAccessToken(): ?string
    {
        return $this->_accessToken;
    }

    public function setRefreshToken(?string $token)
    {
        $this->_refreshToken = $token;
        return $this;
    }

    public function hasRefreshToken(): bool
    {
        return $this->_refreshToken !== null;
    }

    public function getRefreshToken(): ?string
    {
        return $this->_refreshToken;
    }



    // Response
    public function toResponse()
    {
        $isValid = $this->isValid();

        $data = [
            'success' => $isValid,
            'data' => $this->value
        ];

        if ($this->_exception) {
            $data['error'] = [
                'message' => $this->_exception->getMessage(),
                'code' => $this->_exception->getCode(),
                'key' => null
            ];

            if (!Genesis::$environment->isProduction()) {
                $data['error']['file'] = Glitch::normalizePath($this->_exception->getFile());
                $data['error']['line'] = $this->_exception->getLine();
            }
        }

        if (!$this->validator->isValid()) {
            $data['validation'] = $this->validator->data->toArrayDelimitedErrorSet();
        }

        if ($this->hasAccessToken()) {
            $data['tokens']['access'] = $this->_accessToken;
        }

        if ($this->hasRefreshToken()) {
            $data['tokens']['refresh'] = $this->_refreshToken;
        }

        $flags = \JSON_UNESCAPED_SLASHES;

        if (!Genesis::$environment->isProduction()) {
            $flags |= \JSON_PRETTY_PRINT;
        }

        if ($this->_dataProcessor) {
            $data = core\lang\Callback::call($this->_dataProcessor, $data);
        }

        $response = new link\http\response\Stream(
            $content = flex\Json::toString($data, $flags),
            'application/json'
        );

        $headers = $response->getHeaders();
        $headers->setStatusCode($this->getStatusCode());

        if ($this->_cors) {
            $headers->set('Access-Control-Allow-Origin', $this->_cors);
        }

        $headers
            ->setCacheAccess('no-cache')
            ->canStoreCache(false)
            ->shouldRevalidateCache(true);

        return $response;
    }
}
