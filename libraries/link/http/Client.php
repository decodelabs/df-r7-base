<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\http;

use df;
use df\core;
use df\link;

class Client implements IClient {
    
    const VERSION = '0.5';

    protected $_defaultOptions;
    protected $_defaultCookieJar;
    protected $_transport;

    public function getTransport() {
        if(!$this->_transport) {
            $this->_transport = $this->_getDefaultTransport();
        }

        return $this->_transport;
    }

    protected function _getDefaultTransport() {
        return new link\http\transport\Streams();
    }


    public function get($url, $headers=null, $cookies=null) {
        return $this->promiseResponse(
            $this->newRequest($url, 'get', $headers, $cookies)
        );
    }

    public function getFile($url, $destination, $fileName=null, $headers=null, $cookies=null) {
        return $this->promiseResponse(
            $this->newRequest($url, 'get', $headers, $cookies)
                ->withOptions(function($options) use($destination, $fileName) {
                    if($destination instanceof core\io\IWriter) {
                        $options->setDownloadStream($destination);
                    } else {
                        $options->setDownloadFolder($destination);
                    }

                    $options->setDownloadFileName($fileName);
                })
        );
    }

    public function post($url, $data, $headers=null, $cookies=null) {
        return $this->promiseResponse(
            $this->newRequest($url, 'post', $headers, $cookies, $data)
        );
    }

    public function put($url, $data, $headers=null, $cookies=null) {
        return $this->promiseResponse(
            $this->newRequest($url, 'put', $headers, $cookies, $data)
        );
    }

    public function delete($url, $headers=null, $cookies=null) {
        return $this->promiseResponse(
            $this->newRequest($url, 'delete', $headers, $cookies)
        );
    }

    public function head($url, $headers=null, $cookies=null) {
        return $this->promiseResponse(
            $this->newRequest($url, 'head', $headers, $cookies)
        );
    }

    public function options($url, $headers=null, $cookies=null) {
        return $this->promiseResponse(
            $this->newRequest($url, 'options', $headers, $cookies)
        );
    }

    public function patch($url, $data, $headers=null, $cookies=null) {
        return $this->promiseResponse(
            $this->newRequest($url, 'patch', $headers, $cookies, $data)
        );
    }

    public function newRequest($url, $method='get', $headers=null, $cookies=null, $body=null) {
        $request = link\http\request\Base::factory($url);
        $request->setMethod($method);

        if($headers) {
            $request->headers->import(
                link\http\request\HeaderCollection::factory($headers)
            );
        }

        if($cookies) {
            $request->cookies->import($cookies);
        }

        if($body !== null) {
            if($method == 'post' && !is_scalar($body)) {
                $request->getPostData()->import($body);
            } else {
                $request->setBodyData($body);
            }
        }

        return $request;
    }



    public function sendRequest(IRequest $request) {
        return $this->promiseResponse($request)->sync();
    }

    public function promiseResponse(IRequest $request) {
        return $this->getTransport()->promiseResponse($request, $this);
    }


    public function prepareRequest(IRequest $request) {
        if($this->_defaultOptions) {
            $options = $request->options;
            $request->options = clone $this->getDefaultOptions();
            $request->options->import($options);
        }

        $request->options->sanitize();

        if(!$request->headers->has('user-agent')) {
            $request->headers->set('user-agent', $this->getDefaultUserAgent());
        }

        if(!$request->options->cookieJar) {
            $request->options->cookieJar = $this->getDefaultCookieJar();
        }

        $request->options->cookieJar->applyTo($request);
        $request->prepareHeaders();

        return $request;
    }

    public function prepareResponse(IResponse $response, IRequest $request) {
        $response->cookies->sanitize($request);
        $request->options->cookieJar->import($response);

        if($response->isOk()) {
            if($request->options->downloadStream) {
                $response->getContentFileStream()->writeTo(
                    $request->options->downloadStream
                );
            } else if($request->options->downloadFolder) {
                $folder = $request->options->downloadFolder;

                if($folder instanceof core\fs\IFolder) {
                    $folder = $folder->getPath();
                }

                $path = rtrim($folder, '/').'/';

                if($request->options->downloadFileName) {
                    $path .= $request->options->downloadFileName;
                } else {
                    if(!$fileName = $response->headers->getAttachmentFileName()) {
                        if(!$fileName = $request->url->path->getFileName()) {
                            $fileName = 'index';
                        }
                    }

                    $path .= $fileName;
                }

                $response->setContent(core\fs\File::create(
                    $path,
                    $response->getContentFileStream()
                ));
            }
        }

        return $response;
    }

    public function getDefaultUserAgent() {
        $output = 'df-link/'.self::VERSION;

        if(extension_loaded('curl')) {
            $output .= ' curl/'.\curl_version()['version'];
        }

        $output .= ' PHP/'.PHP_VERSION;
        return $output;
    }

    public function setDefaultOptions(IRequestOptions $options=null) {
        $this->_defaultOptions = $options;
        return $this;
    }

    public function getDefaultOptions() {
        if(!$this->_defaultOptions) {
            $this->_defaultOptions = new link\http\request\Options();
        }

        return $this->_defaultOptions;
    }

    public function hasDefaultOptions() {
        return $this->_defaultOptions !== null;
    }


    public function setDefaultCookieJar(ICookieJar $cookieJar=null) {
        $this->_defaultCookieJar = $cookieJar;
        return $this;
    }

    public function getDefaultCookieJar() {
        if(!$this->_defaultCookieJar) {
            $this->_defaultCookieJar = new link\http\cookieJar\Memory();
        }

        return $this->_defaultCookieJar;
    }


    public static function getDefaultCaBundlePath() {
        static $cache = null;

        if($cache) {
            return $cache;
        }

        if($output = ini_get('openssl.cafile')) {
            return $cache = $output;
        }

        if($output = ini_get('curl.cainfo')) {
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

        foreach($files as $file) {
            if(file_exists($file)) {
                return $cache = $file;
            }
        }

        throw new RuntimeException(
            'Unable to find reasonable CA bundle file path'
        );
    }
}