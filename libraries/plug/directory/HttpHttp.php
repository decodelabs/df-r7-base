<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug\directory;

use df;
use df\core;
use df\arch;
use df\halo;
use df\aura;

class HttpHttp implements arch\IDirectoryHelper {
    
    use core\TContextAware;

    protected $_httpRequest;
    
    public function __construct(arch\IContext $context) {
        $this->_context = $context;
        $this->_httpRequest = $this->_context->getApplication()->getHttpRequest();
    }
    
    public function __get($member) {
        switch($member) {
            case 'request':
                return $this->_httpRequest;
                
            case 'method':
                return $this->_httpRequest->getMethod();
                
            case 'host':
                return $this->_httpRequest->getUrl()->getDomain();
                
            case 'headers':
                return $this->_httpRequest->getHeaders();
                
            case 'post':
                return $this->_httpRequest->getPostData();
                
            case 'cookies':
                return $this->_httpRequest->getCookieData();
        }
        
        return null;
    }
    
    
    public function directoryRequestToUrl($request) {
        return $this->_context->getApplication()->requestToUrl(arch\Request::factory($request));
    }
    
    
    public function getRequest() {
        return $this->_httpRequest;
    }
    
    public function getMethod() {
        return $this->_httpRequest->getMethod();
    }
    
    public function getHost() {
        return $this->_httpRequest->getUrl()->getDomain();
    }
    
    public function getHeaders() {
        return $this->_httpRequest->getHeaders();
    }

    public function getReferrer() {
        return $this->_httpRequest->getHeaders()->get('Referer');
    }
    
    public function getPostData() {
        return $this->_httpRequest->getPostData();
    }
    
    public function getCookies() {
        return $this->_httpRequest->getCookieData();
    }


    public function isGetRequest() {
        return $this->getMethod() == 'GET';
    }

    public function isPostRequest() {
        return $this->getMethod() == 'POST';
    }

    public function isPutRequest() {
        return $this->getMethod() == 'PUT';
    }

    public function isDeleteRequest() {
        return $this->getMethod() == 'DELETE';
    }
    
    
    
    public function stringResponse($content, $contentType=null) {
        return new halo\protocol\http\response\String($content, $contentType);
    }

    public function ajaxResponse(aura\view\IView $view, array $extraData=array()) {
        return $this->_context->http->stringResponse(
            $this->_context->data->jsonEncode(array_merge(
                ['content' => (string)$view->getContentProvider()->setRenderTarget($view)],
                $extraData
            )),
            'application/json'
        );
    }
    
    public function fileResponse($path, $checkPath=true) {
        return new halo\protocol\http\response\File($path, $checkPath);
    }
    
    public function redirect($request=null) {
        if($request === null) {
            $request = $this->_context->request;
        }
        
        if(is_string($request)) {
            $request = arch\Request::factory($request);
        }

        if($request instanceof arch\IRequest) {
            $url = $this->_context->getApplication()->requestToUrl($request);
        } else {
            $url = halo\protocol\http\Url::factory((string)$request);
        }

        if($url->isJustFragment()) {
            $fragment = $url->getFragment();
            $url = clone $this->_httpRequest->getUrl();
            $url->setFragment($fragment);
        }

        return new halo\protocol\http\response\Redirect($url);
    }
    
    public function redirectExternal($url) {
        return new halo\protocol\http\response\Redirect($url);
    }
    
    public function defaultRedirect($default=null, $success=true) {
        $request = $this->_context->request;
        
        if($success && ($redirect = $request->getRedirectTo())) {
            return $this->redirect($redirect);
        } else if((!$success || ($success && !$request->getRedirectTo()))
            && ($redirect = $request->getRedirectFrom())) {
            return $this->redirect($redirect);
        }
            
        if($default === null) {
            $default = $request->getParent();
        }
        
        return $this->redirect($default);
    }
    
    
    
    public function setCookie($name, $value=null) {
        $application = $this->_context->getApplication();
        $augmentor = $application->getResponseAugmentor();
        
        if($name instanceof halo\protocol\http\IResponseCookie) {
            $cookie = $name;
        } else {
            $cookie = $augmentor->newCookie($name, $value)
                //->setBaseUrl($application->getBaseUrl())
                ;
        }
        
        $augmentor->setCookieForAnyRequest($cookie);
        return $cookie;
    }
    
    public function removeCookie($name) {
        $application = $this->_context->getApplication();
        $augmentor = $application->getResponseAugmentor();
        
        if($name instanceof halo\protocol\http\IResponseCookie) {
            $cookie = $name;
        } else {
            $cookie = $augmentor->newCookie($name, 'deleted')
                //->setBaseUrl($application->getBaseUrl())
                ;
        }
        
        $augmentor->removeCookieForAnyRequest($cookie);
        return $cookie;
    }
}