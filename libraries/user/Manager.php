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
use df\arch;
use df\mesh;

class Manager implements IManager, core\IDumpable {
    
    use core\TManager;
    
    const REGISTRY_PREFIX = 'manager://user';
    const USER_SESSION_NAMESPACE = 'user';
    const CLIENT_SESSION_KEY = 'Client';
    
    public $_session;
    protected $_client;
    private $_accessLockCache = [];
    
    protected function __construct() {
        $this->session = new user\session\Controller();
    }
    
// Client
    public function getClient() {
        if(!$this->_client) {
            $this->_loadClient();
        }
        
        return $this->_client;
    }

    protected function _loadClient() {
        $session = $this->session->getNamespace(self::USER_SESSION_NAMESPACE);
        $this->_client = $session->get(self::CLIENT_SESSION_KEY);
        $regenKeyring = false;
        $isNew = false;

        if($this->_client === null) {
            $this->_client = Client::generateGuest($this);
            $regenKeyring = true;
            $rethrowException = false;
            $isNew = true;
        } else {
            $cache = user\session\Cache::getInstance();
            $regenKeyring = $cache->shouldRegenerateKeyring($this->_client->getKeyringTimestamp());
            $rethrowException = false;
            core\i18n\Manager::getInstance()->setLocale($this->_client->getLanguage().'_'.$this->_client->getCountry());
        }

        if(!$this->_client->isLoggedIn() && $this->_recallIdentity($isNew)) {
            $regenKeyring = false;
        }

        if($regenKeyring) {
            try {
                if($this->_client->isLoggedIn()) {
                    $this->refreshClientData();
                } else {
                    $this->_client->setKeyring(
                        $this->getUserModel()->generateKeyring($this->_client)
                    );

                    $session->set(self::CLIENT_SESSION_KEY, $this->_client);
                }
            } catch(\Exception $e) {
                if($rethrowException) {
                    throw $e;
                }
            }

        }
    }

    private function _recallIdentity($isNew) {
        if($key = $this->session->getPerpetuator()->getRecallKey($this->session)) {
            if($this->authenticateRecallKey($key)) {
                return true;
            }
        }

        $canRecall = $this->session->getPerpetuator()->canRecallIdentity();

        if($canRecall) {
            $config = user\authentication\Config::getInstance();

            foreach($config->getEnabledAdapters() as $name => $options) {
                try {
                    $adapter = $this->loadAuthenticationAdapter($name);
                } catch(AuthenticationException $e) {
                    continue;
                }

                if(!$adapter instanceof user\authentication\IIdentityRecallAdapter) {
                    continue;
                }

                $request = $adapter->recallIdentity();

                if(!$request instanceof user\authentication\IRequest) {
                    continue;
                }

                if($this->authenticate($request)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function clearClient() {
        $this->_client = null;
        $this->_accessLockCache = [];
        return $this;
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
        } else if(substr($lock, 0, 12) == 'directory://') {
            $lock = new arch\Request($lock);
        } else {
            try {
                $parts = explode('#', $lock);
                $entityId = array_shift($parts);
                $action = array_shift($parts);

                $meshManager = mesh\Manager::getInstance();
                $lock = $meshManager->fetchEntity($entityId);
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


// Session
    public function getSessionBackend() {
        $model = axis\Model::factory('session');

        if(!$model instanceof user\session\IBackend) {
            throw new LogicException(
                'Session model does not implement user\\session\\IBackend'
            );
        }

        return $model;
    }
    
    public function getSessionController() {
        return $this->session;
    }

    public function getSessionNamespace($namespace) {
        return $this->session->getNamespace($namespace);
    }

    public function getSessionStartTime() {
        return $this->session->getDescriptor()->startTime;
    }



// Options
    public function setClientOption($key, $value) {
        return $this->setClientOptions([$key => $value]);
    }

    public function getClientOption($key, $default=null) {
        $client = $this->getClient();
        $this->_ensureClientOptions($client);
        return $client->getOption($key, $default);
    }

    public function setClientOptions(array $options) {
        $client = $this->getClient();
        $this->_ensureClientOptions($client);
        $this->getUserModel()->updateClientOptions($client->getId(), $options);

        $options = array_merge($client->getOptions(), $options);
        $client->importOptions($options);

        $session = $this->session->getNamespace(self::USER_SESSION_NAMESPACE);
        $session->set(self::CLIENT_SESSION_KEY, $client);
        
        return $this;
    }

    public function getClientOptions() {
        $client = $this->getClient();
        $this->_ensureClientOptions($client);
        return $client->getOptions();
    }

    private function _ensureClientOptions($client) {
        if($client->hasOptions()) {
            return;
        }

        $options = $this->getUserModel()->fetchClientOptions($client->getId());

        if(!is_array($options)) {
            $options = [];
        }

        $client->importOptions($options);
        return $client;
    }

    
    
    
// Authentication
    public function isLoggedIn() {
        return $this->getClient()->isLoggedIn();
    }

    public function loadAuthenticationAdapter($name) {
        $class = 'df\\user\\authentication\\adapter\\'.ucfirst($name);
        
        if(!class_exists($class)) {
            throw new AuthenticationException(
                'Authentication adapter '.$name.' could not be found'
            );
        }

        return new $class($this);
    }

    public function authenticate(user\authentication\IRequest $request) {
        $timer = new core\time\Timer();
        
        // Get adapter
        $name = $request->getAdapterName();
        $adapter = $this->loadAuthenticationAdapter($name);

        $config = user\authentication\Config::getInstance();

        if(!$config->isAdapterEnabled($name)) {
            throw new AuthenticationException(
                'Authentication adapter '.$name.' is not enabled'
            );
        }
        
        $model = $this->getUserModel();
        
        // Fetch user
        $result = new user\authentication\Result($name);
        $result->setIdentity($request->getIdentity());
        
        // Authenticate
        $adapter->authenticate($request, $result);
        
        if($result->isValid()) {
            $domainInfo = $result->getDomainInfo();
            $session = $this->session->getNamespace(self::USER_SESSION_NAMESPACE);
            $this->_accessLockCache = [];

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
            
            $clientData->onAuthentication($client);
            

            // Remember me
            if($request->getAttribute('rememberMe')) {
                $this->session->perpetuateRecall($client);
            }

            // Options
            $this->_ensureClientOptions($client);

            // Store session
            $session->set(self::CLIENT_SESSION_KEY, $client);
        }
        
        return $result;
    }

    public function authenticateRecallKey(user\session\RecallKey $key) {
        if(!$this->session->hasRecallKey($key)) {
            return false;
        }

        $session = $this->session->getNamespace(self::USER_SESSION_NAMESPACE);
        $this->_accessLockCache = [];

        $model = $this->getUserModel();
        $clientData = $model->getClientData($key->userId);
        $this->_client = Client::factory($clientData);

        // Set state
        $this->_client->setAuthenticationState(IState::BOUND);
        $this->_client->setKeyring($model->generateKeyring($this->_client));

        $clientData->onAuthentication($this->_client);


        // Remember me
        $this->session->perpetuateRecall($this->_client, $key);

        // Options
        $this->_ensureClientOptions($this->_client);
        
        // Store session
        $session->set(self::CLIENT_SESSION_KEY, $this->_client);

        return true;
    }

    public function refreshClientData() {
        $client = $this->getClient();

        if($this->isLoggedIn()) {
            $model = $this->getUserModel();
            $data = $model->getClientData($client->getId());
            $client->import($data);
            $this->_ensureClientOptions($client);
        }

        $this->regenerateKeyring();

        $session = $this->session->getNamespace(self::USER_SESSION_NAMESPACE);
        $session->set(self::CLIENT_SESSION_KEY, $client);

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
        $this->_ensureClientOptions($client);

        $session = $this->session->getNamespace(self::USER_SESSION_NAMESPACE);
        $session->set(self::CLIENT_SESSION_KEY, $client);
        return $this;
    }

    public function getUserModel() {
        $model = axis\Model::factory('user');
        
        if(!$model instanceof IUserModel) {
            throw new AuthenticationException(
                'User model does not implement user\\IUserModel'
            );
        }
        
        return $model;
    }

    public function logout() {
        $this->session->destroy();
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
    

    
// Passwords
    public function analyzePassword($password) {
        return new core\string\PasswordAnalyzer($password, df\Launchpad::$application->getPassKey());
    }
    
    public function __get($member) {
        switch($member) {
            case 'client':
                return $this->getClient();

            case 'session':
                return $this->session;
        }
    }
    
// Dump
    public function getDumpProperties() {
        return [
            'client' => $this->_client,
            'session' => $this->session
        ];
    }
}
