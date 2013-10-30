<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\user\session;

use df;
use df\core;
use df\user;
use df\axis;
    
class Controller implements IController {

    use core\TApplicationAware;

    const GC_PROBABILITY = 3;
    const TRANSITION_PROBABILITY = 10;
    const TRANSITION_LIFETIME = 10;
    const TRANSITION_COOLOFF = 20;

    protected $_descriptor;
    protected $_perpetuator;
    protected $_backend;
    protected $_cache;
    protected $_isOpen = false;
    protected $_namespaces = array();

    public function __construct(core\IApplication $application) {
        $this->_application = $application;
    }

    public function isOpen() {
        return $this->_isOpen;
    }

// Perpetuator
    public function setPerpetuator(IPerpetuator $perpetuator) {
        if($this->_isOpen) {
            throw new RuntimeException(
                'Cannot set session perpetuator, the session has already started'
            );
        }
        
        $this->_perpetuator = $perpetuator;
        return $this;
    }
    
    public function getPerpetuator() {
        return $this->_perpetuator;
    }

    protected function _loadPerpetuator() {
        switch($this->_application->getRunMode()) {
            case 'Http':
                $this->_perpetuator = new user\session\perpetuator\Cookie($this);
                break;
                
            default:
                $this->_perpetuator = new user\session\perpetuator\Shell($this);
                break;
        }
    }
    

// Backend
    public function setBackend(IBackend $backend) {
        if($this->_isOpen) {
            throw new RuntimeException(
                'Cannot set session backend, the session has already started'
            );
        }
        
        $this->_backend = $backend;
        return $this;
    }
    
    public function getBackend() {
        return $this->_backend;
    }

    protected function _loadBackend() {
        if(axis\ConnectionConfig::getInstance($this->_application)->isSetup()) {
            $this->_backend = $this->_getUserModel()->getSessionBackend();
        }

        if(!$this->_backend instanceof IBackend) {
            $this->_backend = new user\session\backend\Sqlite($this);
        }
    }
    

// Cache
    public function getCache() {
        return $this->_cache;
    }


// Descriptor
    public function getDescriptor() {
        $this->_open();
        return $this->_descriptor;
    }
    
    public function getId() {
        return $this->getDescriptor()->getExternalId();
    }

    public function transitionId() {
        $this->_open();
        
        if($this->_descriptor->hasJustStarted()
        || $this->_descriptor->hasJustTransitioned(self::TRANSITION_COOLOFF)) {
            return $this;
        }
        
        $this->_cache->removeDescriptor($this->_descriptor);
        $this->_descriptor->applyTransition($this->_generateId());
        $this->_backend->applyTransition($this->_descriptor);
        $this->_perpetuator->perpetuate($this, $this->_descriptor);
        
        return $this;
    }

    protected function _generateId() {
        do {
            $output = core\string\Generator::sessionId(
                $this->_application->getPassKey()
            );
        } while($this->_backend->idExists($output));
        
        return $output;
    }
    

// Handlers
    protected function _open() {
        if($this->_isOpen) {
            return;
        }
        
        $this->_isOpen = true;
        
        if($this->_cache === null) {
            $this->_cache = Cache::getInstance($this->_application);
        }
        
        if($this->_backend === null) {
            $this->_loadBackend();
        }
        
        if($this->_perpetuator === null) {
            $this->_loadPerpetuator();
        }
        
        $externalId = $this->_perpetuator->getInputId();
        
        if(empty($externalId)) {
            $this->_descriptor = $this->_start();
        } else {
            $this->_descriptor = $this->_resume($externalId);
        }
        
        $this->_perpetuator->perpetuate($this, $this->_descriptor);
        
        if((mt_rand() % 100) < self::GC_PROBABILITY) {
            $this->_backend->collectGarbage();
            $this->_getUserModel()->purgeRememberKeys();
        }
        
        if(!$this->_descriptor->hasJustTransitioned(120)
        || ((mt_rand() % 100) < self::TRANSITION_PROBABILITY)) {
            $this->transitionId();
        }
        
        if($this->_descriptor->needsTouching(self::TRANSITION_LIFETIME)) {
            $this->_backend->touchSession($this->_descriptor);
            $this->_cache->insertDescriptor($this->_descriptor);
        }
    }


    protected function _start() {
        $time = time();
        $externalId = $this->_generateId();
        
        $descriptor = new Descriptor($externalId, $externalId);
        $descriptor->setStartTime($time);
        $descriptor->setAccessTime($time);
        
        $output = $this->_backend->insertDescriptor($descriptor);
        $output->hasJustStarted(true);
        
        $this->_cache->insertDescriptor($descriptor);
        
        return $output;
    }
    
    protected function _resume($externalId) {
        $descriptor = $this->_cache->fetchDescriptor($externalId);
        
        if(!$descriptor) {
            $descriptor = $this->_backend->fetchDescriptor(
                $externalId, time() - self::TRANSITION_LIFETIME
            );
            
            if($descriptor) {
                $this->_cache->insertDescriptor($descriptor);
            }
        }
        
        if($descriptor === null) {
            return $this->_start();
        }
        
        if(!$descriptor->hasJustTransitioned(self::TRANSITION_LIFETIME)) {
            $descriptor->transitionId = null;
        }
        
        // TODO: check accessTime is within perpetuator life time
        
        return $descriptor;
    }
    
    
    
    
    
    public function getNamespace($namespace) {
        if(!isset($this->_namespaces[$namespace])) {
            $this->_namespaces[$namespace] = new Handler($this, $namespace);
        }
        
        return $this->_namespaces[$namespace];
    }
    
    public function destroy() {
        $this->_open();

        if($this->_perpetuator) {
            $key = $this->_perpetuator->getRememberKey($this);
            $this->_perpetuator->destroy($this);

            if($key) {
                $this->_getUserModel()->destroyRememberKey($key);
            }
        }
        
        $this->_cache->removeDescriptor($this->_descriptor);
        $this->_backend->killSession($this->_descriptor);
        $this->_descriptor = null;
        $this->_namespaces = array();
        $this->_isOpen = false;
        
        $this->_getManager()->clearClient();
        return $this;
    }

    private function _getManager() {
        return user\Manager::getInstance($this->_application);
    }

    private function _getUserModel() {
        return $this->_getManager()->getUserModel();
    }
}