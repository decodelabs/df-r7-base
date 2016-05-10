<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug;

use df;
use df\core;
use df\arch;
use df\link;
use df\aura;
use df\flex;

class Http implements arch\IDirectoryHelper {

    use arch\TDirectoryHelper;

    protected $_httpRequest;

    protected function _init() {
        if(!$this->context->application instanceof core\application\Http) {
            throw new core\RuntimeException(
                'Http helper can only be used from http run mode'
            );
        }

        $this->_httpRequest = $this->context->application->getHttpRequest();
    }

    public function __get($member) {
        switch($member) {
            case 'request':
                return $this->_httpRequest;

            case 'method':
                return $this->_httpRequest->getMethod();

            case 'host':
                return $this->_httpRequest->url->getDomain();

            case 'headers':
                return $this->_httpRequest->headers;

            case 'post':
                return $this->_httpRequest->getPostData();

            case 'cookies':
                return $this->_httpRequest->cookies;
        }

        return null;
    }


    public function directoryRequestToUrl($request) {
        return core\application\http\Router::getInstance()
            ->requestToUrl(arch\Request::factory($request));
    }

    public function getRouter() {
        return $this->context->application->getRouter();
    }

    public function getRequest() {
        return $this->_httpRequest;
    }

    public function getMethod() {
        return $this->_httpRequest->getMethod();
    }

    public function getUrl() {
        return $this->_httpRequest->url;
    }

    public function getHost() {
        return $this->_httpRequest->url->getDomain();
    }

    public function getHeaders() {
        return $this->_httpRequest->headers;
    }

    public function getHeader($key) {
        return $this->_httpRequest->headers->get($key);
    }

    public function getReferrer() {
        return $this->_httpRequest->headers->get('Referer');
    }

    public function getPostData() {
        return $this->_httpRequest->getPostData();
    }

    public function getUserAgent() {
        return $this->_httpRequest->headers->get('User-Agent');
    }

    public function getIp() {
        return $this->_httpRequest->getIp();
    }


    public function isGetRequest() {
        return $this->getMethod() == 'get';
    }

    public function isPostRequest() {
        return $this->getMethod() == 'post';
    }

    public function isPutRequest() {
        return $this->getMethod() == 'put';
    }

    public function isDeleteRequest() {
        return $this->getMethod() == 'delete';
    }

    public function isAjaxRequest() {
        return strtolower($this->_httpRequest->headers->get('x-requested-with')) == 'xmlhttprequest';
    }



// Responses
    public function stringResponse($content, $contentType=null) {
        return new link\http\response\Stream($content, $contentType);
    }

    public function streamResponse($content, $contentType=null) {
        return new link\http\response\Stream($content, $contentType);
    }

    public function ajaxElementResponse(aura\view\IView $view) {
        return $this->stringResponse(
            (string)$view->getContentProvider()->setRenderTarget($view),
            $view->getContentType()
        );
    }

    public function ajaxResponse($content, array $extraData=[]) {
        if($content instanceof aura\view\IView) {
            $content = $this->getAjaxViewContent($content);
        }

        return $this->stringResponse(
            $this->context->data->jsonEncode(array_merge(
                [
                    'node' => $this->context->request->getLiteralPathString(),
                    'content' => $content
                ],
                $extraData
            )),
            'application/json'
        );
    }

    public function ajaxReload(array $extraData=[]) {
        return $this->stringResponse(
            $this->context->data->jsonEncode(array_merge(
                [
                    'node' => $this->context->request->getLiteralPathString(),
                    'reload' => true
                ],
                $extraData
            )),
            'application/json'
        );
    }

    public function getAjaxViewContent(aura\view\IView $view, $showFlashList=false) {
        switch($this->getHeader('x-ajax-request-source')) {
            case 'modal':
                $view = clone $view;
                $content = $view
                    ->setLayout('Modal')
                    ->shouldRenderBase(false)
                    ->render();
                break;

            default:
                $content = (string)$view->getContentProvider()->setRenderTarget($view);

                if($showFlashList) {
                    if($flashList = $this->html->flashList()) {
                        $content = $flashList."\n".$content;
                    }
                }

                break;
        }

        return $content;
    }

    public function jsonResponse(array $data) {
        return $this->streamResponse(
            $this->context->data->jsonEncode($data),
            'application/json'
        );
    }

    public function fileResponse($path, $checkPath=true) {
        return new link\http\response\File($path, $checkPath);
    }

    public function redirect($request=null) {
        $url = $this->context->uri($request);

        if($url->isJustFragment()) {
            $fragment = $url->getFragment();
            $url = clone $this->_httpRequest->url;
            $url->setFragment($fragment);
        }

        return new link\http\response\Redirect($url);
    }

    public function redirectExternal($url) {
        return new link\http\response\Redirect($url);
    }

    public function defaultRedirect($default=null, $success=true, $sectionReferrer=null, $fallback=null) {
        $request = $this->context->request;

        if($success) {
            if(!$redirect = $request->getRedirectTo()) {
                if($default !== null) {
                    $redirect = $default;
                } else {
                    $redirect = $request->getRedirectFrom();
                }
            }

            if($redirect) {
                return $this->redirect($redirect);
            }
        }

        if(!$success && ($redirect = $request->getRedirectFrom()) ){
            return $this->redirect($redirect);
        }

        if($default !== null) {
            return $this->redirect($default);
        }

        if($fallback !== null) {
            return $this->redirect($fallback);
        }

        if($sectionReferrer !== null) {
            if(substr($sectionReferrer, 0, 4) == 'http') {
                $sectionReferrer = $this->localReferrerToRequest($sectionReferrer);
            }

            return $this->redirect($sectionReferrer);
        }

        if($referrer = $this->getReferrer()) {
            $referrer = $this->localReferrerToRequest($referrer);

            if(!$referrer->matches($request)) {
                return $this->redirect($referrer);
            }
        }

        return $this->redirect($request->getParent());
    }

    public function localReferrerToRequest($referrer) {
        try {
            return $this->getRouter()->urlToRequest(link\http\Url::factory($referrer));
        } catch(core\IException $e) {
            return null;
        }
    }


// Generator
    public function generator($contentType, /*core\io\IChunkSender*/ $sender) {
        return new link\http\response\Generator($contentType, $sender);
    }

    public function csvGenerator($fileName, Callable $generator) {
        return $this->generator('text/csv', new flex\csv\Builder($generator))
            ->setAttachmentFileName($fileName);
    }




// Cookies
    public function setCookie($name, $value=null, $expiry=null, $httpOnly=null, $secure=null) {
        $augmentor = $this->context->application->getResponseAugmentor();

        if($name instanceof link\http\ICookie) {
            $cookie = $name;
        } else {
            $cookie = $augmentor->newCookie($name, $value, $expiry, $httpOnly, $secure);
        }

        $augmentor->setCookieForAnyRequest($cookie);
        return $cookie;
    }

    public function getCookie($name, $default=null) {
        return $this->getCookies()->get($name, $default);
    }

    public function hasCookie($name) {
        return $this->getCookies()->has($name);
    }

    public function removeCookie($name) {
        $augmentor = $this->context->application->getResponseAugmentor();

        if($name instanceof link\http\ICookie) {
            $cookie = $name;
        } else {
            $cookie = $augmentor->newCookie($name, 'deleted');
        }

        $augmentor->removeCookieForAnyRequest($cookie);
        return $cookie;
    }

    public function getCookies() {
        return $this->_httpRequest->cookies;
    }

    public function newCookie($name, $value, $expiry=null, $httpOnly=null, $secure=null) {
        return $this->context->application->getResponseAugmentor()->newCookie($name, $value, $expiry, $httpOnly, $secure);
    }

    public function getResponseAugmentor() {
        return $this->context->application->getResponseAugmentor();
    }


// Status
    public static function isValidStatusCode($code) {
        return link\http\response\HeaderCollection::isValidStatusCode($code);
    }

    public static function statusCodeToString($code) {
        return link\http\response\HeaderCollection::statusCodeToString($code);
    }

    public static function statusCodeToMessage($code) {
        return link\http\response\HeaderCollection::statusCodeToMessage($code);
    }

    public static function isInformationStatusCode($code) {
        return link\http\response\HeaderCollection::isInformationStatusCode($code);
    }

    public static function isSuccessStatusCode($code) {
        return link\http\response\HeaderCollection::isSuccessStatusCode($code);
    }

    public static function isRedirectStatusCode($code) {
        return link\http\response\HeaderCollection::isRedirectStatusCode($code);
    }

    public static function isClientErrorStatusCode($code) {
        return link\http\response\HeaderCollection::isClientErrorStatusCode($code);
    }

    public static function isServerErrorStatusCode($code) {
        return link\http\response\HeaderCollection::isServerErrorStatusCode($code);
    }

    public static function isErrorStatusCode($code) {
        return link\http\response\HeaderCollection::isErrorStatusCode($code);
    }
}