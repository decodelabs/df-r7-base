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
            $this->_loadClient();
        }
        
        return $this->_client;
    }

    protected function _loadClient() {
        $session = $this->getSessionNamespace(self::CLIENT_SESSION_NAMESPACE);
        $this->_client = $session->get(self::CLIENT_SESSION_KEY);
        $regenKeyring = false;

        if($this->_client === null) {
            $this->_client = Client::generateGuest($this);
            $regenKeyring = true;
            $rethrowException = false;
        } else {
            $cache = user\session\Cache::getInstance($this->_application);
            $regenKeyring = $cache->shouldRegenerateKeyring($this->_client->getKeyringTimestamp());
            $rethrowException = false;
        }

        if(!$this->_client->isLoggedIn() && ($key = $this->_sessionPerpetuator->getRememberKey($this))) {
            if($this->authenticateRememberKey($key)) {
                $regenKeyring = false;
            }
        }

        if($regenKeyring) {
            try {
                $this->_client->setKeyring(
                    $this->getUserModel()->generateKeyring($this->_client)
                );
            } catch(\Exception $e) {
                if($rethrowException) {
                    throw $e;
                }
            }

            $session->set(self::CLIENT_SESSION_KEY, $this->_client);
        }
    }

    public function canAccess($lock, $action=null, $linkTo=false) {
        if(!$lock instanceof IAccessLock) {
            $lock = $this->getAccessLock($lock);
        }

        return $this->getClient()->canAccess($lock, $action, $linkTo);
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

        if($lock == '*') {
            $lock = (new user\access\lock\Boolean(true))
                ->setAccessLockDomain('*');
        } else if(substr($lock, 0, 10) == 'virtual://') {
            $lock = new user\access\lock\Virtual(substr($lock, 10));
        } else {
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
        }
        
        $this->_accessLockCache[$lockId] = $lock;

        return $lock;
    }
    
    
    
// Authentication
    public function isLoggedIn() {
        return $this->getClient()->isLoggedIn();
    }

    public function authenticate(user\authentication\IRequest $request) {
        $timer = new core\time\Timer();
        
        // Get adapter
        $name = $request->getAdapterName();
        $class = 'df\\user\\authentication\\adapter\\'.$name;
        
        if(!class_exists($class)) {
            throw new AuthenticationException(
                'Authentication adapter '.$name.' could not be found'
            );
        }
        
        $model = $this->getUserModel();
        
        // Fetch user
        $result = new user\authentication\Result($name);
        $result->setIdentity($request->getIdentity());
        $domainInfo = $model->getAuthenticationDomainInfo($request);
        
        if(!$domainInfo instanceof user\authentication\IDomainInfo) {
            $result->setCode(user\authentication\Result::IDENTITY_NOT_FOUND);
            return $result;
        }
        
        // Authenticate
        $result->setDomainInfo($domainInfo);
        $adapter = new $class($this);
        $adapter->authenticate($request, $result);
        
        if($result->isValid()) {
            $session = $this->getSessionNamespace(self::CLIENT_SESSION_NAMESPACE);
            $this->_accessLockCache = array();

            // Import user data
            $domainInfo->onAuthentication();
            $clientData = $domainInfo->getClientData();
            
            if(!$clientData instanceof user\IClientDataObject) {
                throw new AuthenticationException(
                    'Domain info could not provide a valid client data object'
                );
            }
            
            $client = $this->getClient();
            $client->import($clientData);

            // Set state
            $client->setAuthenticationState(IState::CONFIRMED);
            $client->setKeyring($model->generateKeyring($client));
            
            $clientData->onAuthentication();
            

            // Remember me
            if($request->getAttribute('rememberMe')) {
                $perpetuator = $this->getSessionPerpetuator();
                $key = $model->generateRememberKey($client);
                $perpetuator->perpetuateRememberKey($this, $key);
            }


            // Store session
            $session->set(self::CLIENT_SESSION_KEY, $client);
        }
        
        return $result;
    }

    public function authenticateRememberKey(RememberKey $key) {
        $model = $this->getUserModel();

        if(!$model->hasRememberKey($key)) {
            return false;
        }

        $session = $this->getSessionNamespace(self::CLIENT_SESSION_NAMESPACE);
        $this->_accessLockCache = array();

        $clientData = $model->getClientData($key->userId);
        $this->_client = Client::factory($clientData);

        // Set state
        $this->_client->setAuthenticationState(IState::BOUND);
        $this->_client->setKeyring($model->generateKeyring($this->_client));

        $clientData->onAuthentication();


        // Remember me
        $model->destroyRememberKey($key);
        $perpetuator = $this->getSessionPerpetuator();
        $key = $model->generateRememberKey($this->_client);
        $perpetuator->perpetuateRememberKey($this, $key);
        
        // Store session
        $session->set(self::CLIENT_SESSION_KEY, $this->_client);

        return true;
    }

    public function refreshClientData() {
        $model = $this->getUserModel();
        $data = $model->getClientData($this->getClient()->getId());
        $this->importClientData($data);
        $this->regenerateKeyring();

        return $this;
    }

    public function importClientData(user\IClientDataObject $data) {
        $client = $this->getClient();

        if($client->getId() != $data->getId()) {
            throw new AuthenticationException(
                'Client data to import is not for the currently authenticated user'
            );
        }

        $client->import($data);

        $session = $this->getSessionNamespace(self::CLIENT_SESSION_NAMESPACE);
        $session->set(self::CLIENT_SESSION_KEY, $client);
        return $this;
    }

    public function getUserModel() {
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

    public function regenerateKeyring() {
        $client = $this->getClient();

        $client->setKeyring(
            $this->getUserModel()->generateKeyring($client)
        );

        return $this;
    }


    public function instigateGlobalKeyringRegeneration() {
        $cache = user\session\Cache::getInstance();
        $cache->setGlobalKeyringTimestamp();

        return $this;
    }
    
    
    
// Session perpetuator
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

    protected function _loadSessionPerpetuator() {
        switch($this->_application->getRunMode()) {
            case 'Http':
                $this->_sessionPerpetuator = new user\session\perpetuator\Cookie($this);
                break;
                
            default:
                $this->_sessionPerpetuator = new user\session\perpetuator\Shell($this);
                break;
        }
    }
    

// Session backend
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

    protected function _loadSessionBackend() {
        if(axis\ConnectionConfig::getInstance($this->_application)->isSetup()) {
            $this->_sessionBackend = $this->getUserModel()->getSessionBackend();
        }

        if(!$this->_sessionBackend instanceof user\ISessionBackend) {
            $this->_sessionBackend = new user\session\backend\Sqlite($this);
        }
    }
    

// Session cache
    public function getSessionCache() {
        return $this->_sessionCache;
    }


// Session descriptor
    public function getSessionDescriptor() {
        $this->_openSession();
        return $this->_sessionDescriptor;
    }
    
    public function getSessionId() {
        return $this->getSessionDescriptor()->getExternalId();
    }
    

// Session handlers
    protected function _openSession() {
        if($this->_isSessionOpen) {
            return;
        }
        
        $this->_isSessionOpen = true;
        
        if($this->_sessionCache === null) {
            $this->_sessionCache = user\session\Cache::getInstance($this->_application);
        }
        
        if($this->_sessionBackend === null) {
            $this->_loadSessionBackend();
        }
        
        if($this->_sessionPerpetuator === null) {
            $this->_loadSessionPerpetuator();
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
            $this->getUserModel()->purgeRememberKeys();
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

        if($this->_sessionPerpetuator) {
            $key = $this->_sessionPerpetuator->getRememberKey($this);
            $this->_sessionPerpetuator->destroy($this);

            if($key) {
                $this->getUserModel()->destroyRememberKey($key);
            }
        }
        
        $this->_sessionCache->removeDescriptor($this->_sessionDescriptor);
        $this->_sessionBackend->killSession($this->_sessionDescriptor);
        $this->_sessionDescriptor = null;
        $this->_sessionNamespaces = array();
        $this->_isSessionOpen = false;
        
        $this->_client = null;
        
        return $this;
    }
    
    
    
// Passwords
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
