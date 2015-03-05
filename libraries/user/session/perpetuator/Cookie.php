<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\user\session\perpetuator;

use df;
use df\core;
use df\user;
use df\arch;
use df\link;

class Cookie implements user\session\IPerpetuator {
    
    protected $_sessionCookieName = '_s';
    protected $_rememberCookieName = '_r';
    protected $_inputId;
    protected $_canRecall = true;
    
    public function __construct(user\session\IController $controller) {
        $httpRequest = df\Launchpad::$application->getHttpRequest();
        
        if($httpRequest->hasCookieData()) {
            $cookies = $httpRequest->getCookieData();

            if($cookies->has($this->_sessionCookieName)) {
                try {
                    $this->_inputId = hex2bin($cookies->get($this->_sessionCookieName));
                } catch(\Exception $e) {
                    $this->_inputId = null;
                }
            }

            if($cookies->has($this->_rememberCookieName) && $cookies->get($this->_rememberCookieName) == '') {
                $this->_canRecall = false;
            }
        }
    }
    
    public function getInputId() {
        return $this->_inputId;
    }

    public function canRecallIdentity() {
        return $this->_canRecall;
    }
    
    public function perpetuate(user\session\IController $controller, user\session\IDescriptor $descriptor) {
        $outputId = $descriptor->getExternalIdHex();
        
        if($outputId != $this->_inputId) {
            $application = df\Launchpad::$application;
        
            if($application instanceof link\http\IResponseAugmentorProvider) {
                $augmentor = $application->getResponseAugmentor();
                $augmentor->setCookieForAnyRequest($augmentor->newCookie(
                    $this->_sessionCookieName, $outputId, null, true
                ));
            }
        }
        
        return $this;
    }  

    public function destroy(user\session\IController $controller) {
        $application = df\Launchpad::$application;
        
        if($application instanceof link\http\IResponseAugmentorProvider) {
            $augmentor = $application->getResponseAugmentor();

            // Remove session cookie
            $augmentor->removeCookieForAnyRequest($augmentor->newCookie(
                $this->_sessionCookieName, 'deleted', null, true
            ));

            // Set remember cookie to ''
            $augmentor->setCookieForAnyRequest($augmentor->newCookie(
                $this->_rememberCookieName, '', null, true
            ));
        }

        return $this;
    }

    public function perpetuateRecallKey(user\session\IController $controller, user\session\RecallKey $key) {
        $application = df\Launchpad::$application;

        if($application instanceof link\http\IResponseAugmentorProvider) {
            $augmentor = $application->getResponseAugmentor();

            $augmentor->setCookieForAnyRequest($augmentor->newCookie(
                $this->_rememberCookieName, 
                $key->getInterlaceKey(), 
                '+1 month', 
                true
            ));
        }

        return $this;
    }  

    public function getRecallKey(user\session\IController $controller) {
        $httpRequest = df\Launchpad::$application->getHttpRequest();
        
        if($httpRequest->hasCookieData()) {
            $value = $httpRequest->getCookieData()->get($this->_rememberCookieName);

            if(!empty($value)) {
                $key = new user\session\RecallKey(
                    substr($value, 20, 1),
                    substr($value, 0, 20).substr($value, 21)
                );

                return $key;
            }
        }

        return null;
    }

    public function destroyRecallKey(user\session\IController $controller) {
        $application = df\Launchpad::$application;
        
        if($application instanceof link\http\IResponseAugmentorProvider) {
            $augmentor = $application->getResponseAugmentor();

            $augmentor->removeCookieForAnyRequest($augmentor->newCookie(
                $this->_rememberCookieName, '', null, true
            ));
        }

        return $this;
    }
}
