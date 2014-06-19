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
    protected $_logoutCookieName = 'logout';
    protected $_inputId;
    protected $_canRecall = true;
    protected $_lifeTime;
    
    public function __construct(user\session\IController $controller) {
        $httpRequest = df\Launchpad::$application->getHttpRequest();
        
        if($httpRequest->hasCookieData()) {
            $cookies = $httpRequest->getCookieData();
            $this->_inputId = $cookies->get($this->_sessionCookieName);
            $this->_canRecall = !$cookies->has($this->_logoutCookieName);
        }
        
        // TODO: get life time from config
    }
    
    public function setLifeTime($lifeTime) {
        if($lifeTime instanceof core\time\IDuration) {
            $lifeTime = $lifeTime->getSeconds();
        }
        
        $this->_lifeTime = (int)$lifeTime;
        return $this;
    }
    
    public function getLifeTime() {
        return $this->_lifeTime;
    }
    
    public function getInputId() {
        return $this->_inputId;
    }

    public function canRecallIdentity() {
        return $this->_canRecall;
    }
    
    public function perpetuate(user\session\IController $controller, user\session\IDescriptor $descriptor) {
        $outputId = $descriptor->getExternalId();
        
        if($outputId != $this->_inputId) {
            $application = df\Launchpad::$application;
        
            if($application instanceof link\http\IResponseAugmentorProvider) {
                $augmentor = $application->getResponseAugmentor();
                $cookie = $this->_getSessionCookie($application, $outputId);
                $augmentor->setCookieForAnyRequest($cookie);
            }
        }
        
        return $this;
    }  

    protected function _getSessionCookie($application, $outputId) {
        $augmentor = $application->getResponseAugmentor();
                
        return $augmentor->newCookie($this->_sessionCookieName, $outputId)
            ->setBaseUrl($application->getRouter()->getBaseUrl())
            ->isHttpOnly(true);
    }

    public function destroy(user\session\IController $controller) {
        $application = df\Launchpad::$application;
        
        if($application instanceof link\http\IResponseAugmentorProvider) {
            $augmentor = $application->getResponseAugmentor();

            $augmentor->removeCookieForAnyRequest($this->_getSessionCookie(
                $application,
                'deleted'
            ));

            $augmentor->removeCookieForAnyRequest($this->_getRememberCookie(
                $application, 
                $this->getRememberKey($controller)
            ));

            $augmentor->setCookieForAnyRequest(
                $augmentor->newCookie($this->_logoutCookieName, '1')
                    ->setExpiryDate(core\time\Date::factory('+5 minutes'))
                    ->setBaseUrl($application->getRouter()->getBaseUrl())
                    ->isHttpOnly(true)
            );
        }

        return $this;
    }

    public function perpetuateRememberKey(user\session\IController $controller, user\RememberKey $key) {
        $application = df\Launchpad::$application;

        if($application instanceof link\http\IResponseAugmentorProvider) {
            $augmentor = $application->getResponseAugmentor();
            $cookie = $this->_getRememberCookie($application, $key);
            $augmentor->setCookieForAnyRequest($cookie);
        }

        return $this;
    }  

    protected function _getRememberCookie($application, user\RememberKey $key=null) {
        $augmentor = $application->getResponseAugmentor();

        if($key) {
            $value = substr($key->key, 0, 20).$key->userId.substr($key->key, 20);
        } else {
            $value = 'deleted';
        }

        return $augmentor->newCookie($this->_rememberCookieName, $value)
            ->setExpiryDate(core\time\Date::factory('+1 month'))
            ->setBaseUrl($application->getRouter()->getBaseUrl())
            ->isHttpOnly(true);
    }

    public function getRememberKey(user\session\IController $controller) {
        $httpRequest = df\Launchpad::$application->getHttpRequest();
        
        if($httpRequest->hasCookieData()) {
            $value = $httpRequest->getCookieData()->get($this->_rememberCookieName);

            if(!empty($value)) {
                $key = new user\RememberKey();
                $key->key = substr($value, 0, 20).substr($value, 21);
                $key->userId = substr($value, 20, 1);

                return $key;
            }
        }

        return null;
    }

    public function destroyRememberKey(user\session\IController $controller) {
        $application = df\Launchpad::$application;
        
        if($application instanceof link\http\IResponseAugmentorProvider) {
            $augmentor = $application->getResponseAugmentor();

            $augmentor->removeCookieForAnyRequest($this->_getRememberCookie(
                $application, 
                $this->getRememberKey($controller)
            ));
        }

        return $this;
    }
}
