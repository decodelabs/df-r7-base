<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\http;

use df;
use df\core;
use df\link;

use DecodeLabs\Atlas;
use DecodeLabs\Atlas\Mode;
use DecodeLabs\Atlas\Dir;
use DecodeLabs\Atlas\Channel;

class Client implements IClient
{
    const VERSION = '0.5';

    protected $_defaultOptions;
    protected $_defaultCookieJar;
    protected $_transport;

    public function getTransport()
    {
        if (!$this->_transport) {
            $this->_transport = $this->_getDefaultTransport();
        }

        return $this->_transport;
    }

    protected function _getDefaultTransport()
    {
        if (extension_loaded('curl')) {
            return new link\http\transport\Curl();
        }

        return new link\http\transport\Stream();
    }



    public function get($url, $callback=null)
    {
        return $this->promise($url, $callback)->sync();
    }

    public function promise($url, $callback=null)
    {
        return $this->promiseResponse(
            $this->newGetRequest($url, $callback)
        );
    }

    public function getFile($url, $destination, $fileName=null, $callback=null)
    {
        return $this->promiseFile($url, $destination, $fileName, $callback)->sync();
    }

    public function promiseFile($url, $destination, $fileName=null, $callback=null)
    {
        return $this->promiseResponse(
            $this->newGetFileRequest($url, $destination, $fileName, $callback)
        );
    }

    public function post($url, $data, $callback=null)
    {
        return $this->promisePost($url, $data, $callback)->sync();
    }

    public function promisePost($url, $data, $callback=null)
    {
        return $this->promiseResponse(
            $this->newPostRequest($url, $data, $callback)
        );
    }

    public function put($url, $data, $callback=null)
    {
        return $this->promisePut($url, $data, $callback)->sync();
    }

    public function promisePut($url, $data, $callback=null)
    {
        return $this->promiseResponse(
            $this->newPutRequest($url, $data, $callback)
        );
    }

    public function delete($url, $callback=null)
    {
        return $this->promiseDelete($url, $callback)->sync();
    }

    public function promiseDelete($url, $callback=null)
    {
        return $this->promiseResponse(
            $this->newDeleteRequest($url, $callback)
        );
    }

    public function head($url, $callback=null)
    {
        return $this->promiseHead($url, $callback)->sync();
    }

    public function promiseHead($url, $callback=null)
    {
        return $this->promiseResponse(
            $this->newHeadRequest($url, $callback)
        );
    }

    public function options($url, $callback=null)
    {
        return $this->promiseOptions($url, $callback)->sync();
    }

    public function promiseOptions($url, $callback=null)
    {
        return $this->promiseResponse(
            $this->newOptionsRequest($url, $callback)
        );
    }

    public function patch($url, $data, $callback=null)
    {
        return $this->promisePatch($url, $data, $callback)->sync();
    }

    public function promisePatch($url, $data, $callback=null)
    {
        return $this->promiseResponse(
            $this->newPatchRequest($url, $data, $callback)
        );
    }




    public function newRequest($url, $method='get', $callback=null, $body=null)
    {
        $request = link\http\request\Base::factory($url);
        $request->setMethod($method);

        core\lang\Callback::call($callback, $request, $this);

        if ($body !== null) {
            if ($method == 'post' && !is_scalar($body)) {
                $request->getPostData()->import($body);
            } else {
                $request->setBodyData($body);
            }
        }

        return $request;
    }

    public function newGetRequest($url, $callback=null)
    {
        return $this->newRequest($url, 'get', $callback);
    }

    public function newGetFileRequest($url, $destination, $fileName=null, $callback=null)
    {
        return $this->newRequest($url, 'get', $callback)
            ->withOptions(function ($options) use ($destination, $fileName) {
                if ($destination instanceof Channel) {
                    $options->setDownloadStream($destination);
                } else {
                    $options->setDownloadFolder($destination);
                }

                $options->setDownloadFileName($fileName);
            });
    }

    public function newPostRequest($url, $data, $callback=null)
    {
        return $this->newRequest($url, 'post', $callback, $data);
    }

    public function newPutRequest($url, $data, $callback=null)
    {
        return $this->newRequest($url, 'put', $callback, $data);
    }

    public function newDeleteRequest($url, $callback=null)
    {
        return $this->newRequest($url, 'delete', $callback);
    }

    public function newHeadRequest($url, $callback=null)
    {
        return $this->newRequest($url, 'head', $callback);
    }

    public function newOptionsRequest($url, $callback=null)
    {
        return $this->newRequest($url, 'options', $callback);
    }

    public function newPatchRequest($url, $data, $callback=null)
    {
        return $this->newRequest($url, 'patch', $callback, $data);
    }



    public function newPool()
    {
        return new link\http\request\Pool($this);
    }



    public function sendRequest(IRequest $request)
    {
        return $this->promiseResponse($request)->sync();
    }

    public function promiseResponse(IRequest $request)
    {
        return $this->getTransport()->promiseResponse($request, $this);
    }


    public function prepareRequest(IRequest $request)
    {
        $url = $request->getUrl();

        if (!$url->hasScheme()) {
            $url->setScheme('http');
        }

        if ($this->_defaultOptions) {
            $oldOptions = $request->getOptions();
            $options = clone $this->getDefaultOptions();
            $options->import($oldOptions);
            $request->setOptions($options);
        }

        $options = $request->getOptions();
        $options->sanitize();
        $headers = $request->getHeaders();

        if (!$headers->has('user-agent')) {
            $headers->set('user-agent', $this->getDefaultUserAgent());
        }

        if ($request->getMethod() == 'post' && !$headers->has('content-type')) {
            $headers->set('content-type', 'application/x-www-form-urlencoded');
        }

        if (!$options->cookieJar) {
            $options->cookieJar = $this->getDefaultCookieJar();
        }

        $options->cookieJar->applyTo($request);
        $request->prepareHeaders();

        return $request;
    }

    public function prepareResponse(IResponse $response, IRequest $request)
    {
        $options = $request->getOptions();
        $response->getCookies()->sanitize($request);
        $options->cookieJar->import($response);

        $target = null;
        $stream = $response->getContentFileStream();
        $isOk = $response->isOk();

        if ($isOk && $options->downloadStream) {
            $target = $options->downloadStream;
        } elseif ($isOk && $options->downloadFolder) {
            $folder = $options->downloadFolder;

            if ($folder instanceof Dir) {
                $folder = $folder->getPath();
            }

            $path = rtrim($folder, '/').'/';

            if ($options->downloadFileName) {
                $path .= $options->downloadFileName;
            } else {
                if (!$fileName = $response->getHeaders()->getAttachmentFileName()) {
                    if (!$fileName = $request->getUrl()->getPath()->getBaseName()) {
                        $fileName = 'index';
                    }
                }

                $path .= $fileName;
            }

            $target = Atlas::$fs->file($path, Mode::READ_WRITE_TRUNCATE);
        }

        if ($target && $response instanceof IAdaptiveStreamResponse) {
            $response->setContentFileStream($target);
        }

        return $response;
    }

    public function getDefaultUserAgent()
    {
        $output = 'df-link/'.self::VERSION;

        if (extension_loaded('curl')) {
            $output .= ' curl/'.\curl_version()['version'];
        }

        $output .= ' PHP/'.PHP_VERSION;
        return $output;
    }

    public function setDefaultOptions(IRequestOptions $options=null)
    {
        $this->_defaultOptions = $options;
        return $this;
    }

    public function getDefaultOptions()
    {
        if (!$this->_defaultOptions) {
            $this->_defaultOptions = new link\http\request\Options();
        }

        return $this->_defaultOptions;
    }

    public function hasDefaultOptions()
    {
        return $this->_defaultOptions !== null;
    }


    public function setDefaultCookieJar(ICookieJar $cookieJar=null)
    {
        $this->_defaultCookieJar = $cookieJar;
        return $this;
    }

    public function getDefaultCookieJar()
    {
        if (!$this->_defaultCookieJar) {
            $this->_defaultCookieJar = new link\http\cookieJar\Memory();
        }

        return $this->_defaultCookieJar;
    }


    public static function getDefaultCaBundlePath()
    {
        static $cache = null;

        if ($cache) {
            return $cache;
        }

        if ($output = ini_get('openssl.cafile')) {
            return $cache = $output;
        }

        if ($output = ini_get('curl.cainfo')) {
            return $cache = $output;
        }

        static $files = [
            '/etc/pki/tls/certs/ca-bundle.crt',
            '/etc/ssl/certs/ca-certificates.crt',
            '/usr/local/share/certs/ca-root-nss.crt',
            '/usr/local/etc/openssl/cert.pem',
            'C:\\windows\\system32\\curl-ca-bundle.crt',
            'C:\\windows\\curl-ca-bundle.crt'
        ];

        foreach ($files as $file) {
            if (file_exists($file)) {
                return $cache = $file;
            }
        }

        throw new RuntimeException(
            'Unable to find reasonable CA bundle file path'
        );
    }
}
