<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\user;

use df;
use df\core;
use df\user;
use df\axis;

class Manager implements IManager, core\IDumpable {
    
    use core\TManager;
    
    const REGISTRY_PREFIX = 'manager://user';
    const CLIENT_SESSION_NAMESPACE = 'user';
    const CLIENT_SESSION_KEY = 'Client';
    
    const SESSION_GC_PROBABILITY = 3;
    const SESSION_TRANSITION_PROBABILITY = 10;
    const SESSION_TRANSITION_LIFETIME = 10;
    const SESSION_TRANSITION_COOLOFF = 20;
    
    protected $_client;
    
    protected $_sessionDescriptor;
    protected $_sessionPerpetuator;
    protected $_sessionBackend;
    protected $_sessionCache;
    protected $_isSessionOpen = false;
    protected $_sessionNamespaces = array();

    private $_accessLockCache = array();
    
    
// Client
    public function getClient() {
        if(!$this->_client) {
            $session = $this->getSessionNamespace(self::CLIENT_SESSION_NAMESPACE);
            $this->_client = $session->get(self::CLIENT_SESSION_KEY);
            $regenKeyring = false;

            if($this->_client === null) {
                $this->_client = Client::generateGuest($this);
                $regenKeyring = true;
            } else {
                $cache = user\session\Cache::getInstance($this->_application);
                $regenKeyring = $cache->shouldRegenerateKeyring($this->_client->getKeyringTimestamp());
            }

            if($regenKeyring) {
                $notify = \df\arch\notify\Manager::getInstance();
                $notify->setInstantMessage($notify->newMessage('keyring.regen', 'Regenerating client keyring', 'debug'));

                try {
                    $this->_client->setKeyring(
                        $this->_getUserModel()->generateKeyring($this->_client)
                    );
                } catch(\Exception $e) {
                    core\debug()->exception($e);
                }

                $session->set(self::CLIENT_SESSION_KEY, $this->_client);
            }
            
            return $this->_client;
        }
        
        return $this->_client;
    }

    public function canAccess($lock, $action=null) {
        if(!$lock instanceof IAccessLock) {
            $lock = $this->getAccessLock($lock);
        }

        return $this->getClient()->canAccess($lock, $action);
    }

    public function getAccessLock($lock) {
        if($lock instanceof user\IAccessLock) {
            return $lock;
        }

        if(is_bool($lock)) {
            return new user\access\lock\Boolean($lock);
        }

        $lock = $lockId = (string)$lock;

        if(isset($this->_accessLockCache[$lockId])) {
            return $this->_accessLockCache[$lockId];
        }

        try {
            $parts = explode('#', $lock);
            $policyId = array_shift($parts);
            $action = array_shift($parts);

            $policy = core\policy\Manager::getInstance($this->_application);
            $lock = $policy->fetchEntity($policyId);
        } catch(\Exception $e) {
            $lock = new user\access\lock\Boolean(true);
        }
        
        if($action !== null) {
            $lock = $lock->getActionLock($action);
        }

        $this->_accessLockCache[$lockId] = $lock;

        return $lock;
    }
    
    
    
// Authentication
    public function authenticate(user\authentication\IRequest $request) {
        $timer = new core\time\Timer();
        
        $name = $request->getAdapterName();
        $class = 'df\\user\\authentication\\adapter\\'.$name;
        
        if(!class_exists($class)) {
            throw new AuthenticationException(
                'Authentication adapter '.$name.' could not be found'
            );
        }
        
        $model = $this->_getUserModel();
        
        $result = new user\authentication\Result($name);
        $result->setIdentity($request->getIdentity());
        $domainInfo = $model->getAuthenticationDomainInfo($request);
        
        if(!$domainInfo instanceof user\authentication\IDomainInfo) {
            $result->setCode(user\authentication\Result::IDENTITY_NOT_FOUND);
            return $result;
        }
        
        $result->setDomainInfo($domainInfo);
        $adapter = new $class($this);
        $adapter->authenticate($request, $result);
        
        if($result->isValid()) {
            $this->_accessLockCache = array();

            $domainInfo->onAuthentication();
            $clientData = $domainInfo->getClientData();
            
            if(!$clientData instanceof user\IClientDataObject) {
                throw new AuthenticationException(
                    'Domain info could not provide a valid client data object'
                );
            }
            
            $client = $this->getClient();
            $client->import($clientData);

            $client->setAuthenticationState(IState::CONFIRMED);
            $client->setKeyring($model->generateKeyring($client));
            
            $clientData->onAuthentication();
            
            $session = $this->getSessionNamespace(self::CLIENT_SESSION_NAMESPACE);
            $session->set(self::CLIENT_SESSION_KEY, $client);
        }
        
        return $result;
    }

    protected function _getUserModel() {
        $model = axis\Model::factory('user', $this->_application);
        
        if(!$model instanceof IUserModel) {
            throw new AuthenticationException(
                'User model does not implement user\\IUserModel'
            );
        }
        
        return $model;
    }
    
    public function logout() {
        $this->destroySession();
        return $this;
    }



    public function instigateGlobalKeyringRegeneration() {
        $cache = user\session\Cache::getInstance();
        $cache->setGlobalKeyringTimestamp();

        return $this;
    }
    
    
    
// Session
    public function setSessionPerpetuator(ISessionPerpetuator $perpetuator) {
        if($this->_sessionIsOpen) {
            throw new RuntimeException(
                'Cannot set session perpetuator, the session has already started'
            );
        }
        
        $this->_sessionPerpetuator = $perpetuator;
        return $this;
    }
    
    public function getSessionPerpetuator() {
        return $this->_sessionPerpetuator;
    }
    
    public function setSessionBackend(ISessionBackend $backend) {
        if($this->_sessionIsOpen) {
            throw new RuntimeException(
                'Cannot set session backend, the session has already started'
            );
        }
        
        $this->_sessionBackend = $backend;
        return $this;
    }
    
    public function getSessionBackend() {
        return $this->_sessionBackend;
    }
    
    public function getSessionCache() {
        return $this->_sessionCache;
    }
    
    public function getSessionDescriptor() {
        $this->_openSession();
        return $this->_sessionDescriptor;
    }
    
    public function getSessionId() {
        return $this->getSessionDescriptor()->getExternalId();
    }
    
    protected function _openSession() {
        if($this->_isSessionOpen) {
            return;
        }
        
        $this->_isSessionOpen = true;
        $this->_sessionCache = user\session\Cache::getInstance($this->_application);
        
        if($this->_sessionBackend === null) {
            // TODO: get backend from config
            
            if($this->_application->isDistributed()) {
                core\stub('need a remote shared session backend');
            } else {
                $this->_sessionBackend = new user\session\backend\Sqlite($this);
            }
        }
        
        if($this->_sessionPerpetuator === null) {
            switch($this->_application->getRunMode()) {
                case 'Http':
                    $this->_sessionPerpetuator = new user\session\perpetuator\Cookie($this);
                    break;
                    
                default:
                    core\stub($this->_application);
            }
        }
        
        $externalId = $this->_sessionPerpetuator->getInputId();
        
        if(empty($externalId)) {
            $this->_sessionDescriptor = $this->_startSession();
        } else {
            $this->_sessionDescriptor = $this->_resumeSession($externalId);
        }
        
        $this->_sessionPerpetuator->perpetuate($this, $this->_sessionDescriptor);
        
        if((mt_rand() % 100) < self::SESSION_GC_PROBABILITY) {
            $this->_sessionBackend->collectGarbage();
        }
        
        if(!$this->_sessionDescriptor->hasJustTransitioned(120)
        || ((mt_rand() % 100) < self::SESSION_TRANSITION_PROBABILITY)) {
            $this->transitionSessionId();
        }
        
        if($this->_sessionDescriptor->needsTouching(self::SESSION_TRANSITION_LIFETIME)) {
            $this->_sessionBackend->touchSession($this->_sessionDescriptor);
            $this->_sessionCache->insertDescriptor($this->_sessionDescriptor);
        }
    }


    protected function _startSession() {
        $time = time();
        $externalId = $this->_generateSessionId();
        
        $descriptor = new user\session\Descriptor($externalId, $externalId);
        $descriptor->setStartTime($time);
        $descriptor->setAccessTime($time);
        
        $output = $this->_sessionBackend->insertDescriptor($descriptor);
        $output->hasJustStarted(true);
        
        $this->_sessionCache->insertDescriptor($descriptor);
        
        return $output;
    }
    
    protected function _resumeSession($externalId) {
        $descriptor = $this->_sessionCache->fetchDescriptor($externalId);
        
        if(!$descriptor) {
            $descriptor = $this->_sessionBackend->fetchDescriptor(
                $externalId, time() - self::SESSION_TRANSITION_LIFETIME
            );
            
            if($descriptor) {
                $this->_sessionCache->insertDescriptor($descriptor);
            }
        }
        
        if($descriptor === null) {
            return $this->_startSession();
        }
        
        if(!$descriptor->hasJustTransitioned(self::SESSION_TRANSITION_LIFETIME)) {
            $descriptor->transitionId = null;
        }
        
        // TODO: check accessTime is within perpetuator life time
        
        return $descriptor;
    }
    
    protected function _generateSessionId() {
        do {
            $output = core\string\Generator::sessionId(
                $this->_application->getPassKey()
            );
        } while($this->_sessionBackend->idExists($output));
        
        return $output;
    }
    
    public function transitionSessionId() {
        $this->_openSession();
        
        if($this->_sessionDescriptor->hasJustStarted()
        || $this->_sessionDescriptor->hasJustTransitioned(self::SESSION_TRANSITION_COOLOFF)) {
            return $this;
        }
        
        $this->_sessionCache->removeDescriptor($this->_sessionDescriptor);
        $this->_sessionDescriptor->applyTransition($this->_generateSessionId());
        $this->_sessionBackend->applyTransition($this->_sessionDescriptor);
        $this->_sessionPerpetuator->perpetuate($this, $this->_sessionDescriptor);
        
        return $this;
    }
    
    public function isSessionOpen() {
        return $this->_isSessionOpen;
    }
    
    public function getSessionNamespace($namespace) {
        if(!isset($this->_sessionNamespaces[$namespace])) {
            $this->_sessionNamespaces[$namespace] = new user\session\Handler($this, $namespace);
        }
        
        return $this->_sessionNamespaces[$namespace];
    }
    
    public function destroySession() {
        $this->_accessLockCache = array();
        $this->_openSession();
        
        $this->_sessionCache->removeDescriptor($this->_sessionDescriptor);
        $this->_sessionBackend->killSession($this->_sessionDescriptor);
        $this->_sessionDescriptor = null;
        $this->_sessionNamespaces = array();
        $this->_isSessionOpen = false;
        
        $this->_client = null;
        
        return $this;
    }
    
    
    
    
    public function analyzePassword($password) {
        return new core\string\PasswordAnalyzer($password, $this->_application->getPassKey());
    }
    
    public function __get($member) {
        switch($member) {
            case 'client':
                return $this->getClient();
        }
    }
    
// Dump
    public function getDumpProperties() {
        return [
            'client' => $this->_client,
            'session' => [
                'backend' => $this->_sessionBackend,
                'perpetuator' => $this->_sessionPerpetuator,
                'descriptor' => $this->_sessionDescriptor,
                'namespaces' => $this->_sessionNamespaces
            ]
        ];
    }
}
