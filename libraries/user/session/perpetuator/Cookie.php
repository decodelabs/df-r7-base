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
use df\halo;

class Cookie implements user\ISessionPerpetuator {
    
    protected $_cookieName = '_s';
    protected $_inputId;
    protected $_lifeTime;
    
    public function __construct(user\IManager $manager) {
        $httpRequest = $manager->getApplication()->getHttpRequest();
        
        if($httpRequest->hasCookieData()) {
            $this->_inputId = $httpRequest->getCookieData()->get($this->_cookieName);
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
    
    public function perpetuate(user\IManager $manager, user\ISessionDescriptor $descriptor) {
        $outputId = $descriptor->getExternalId();
        
        if($outputId != $this->_inputId) {
            $application = $manager->getApplication();
        
            if($application instanceof halo\protocol\http\IResponseAugmentorProvider) {
                $augmentor = $application->getResponseAugmentor();
                
                $cookie = $augmentor->newCookie($this->_cookieName, $outputId)
                    ->setBaseUrl($application->getBaseUrl())
                    ->isHttpOnly(true);
                    
                $augmentor->setCookieForAnyRequest($cookie);
            }
        }
        
        return $this;
    }    
}
