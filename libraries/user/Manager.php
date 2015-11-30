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
use df\flex;
use df\mesh;

class Manager implements IManager, core\IShutdownAware, core\IDumpable {

    use core\TManager;

    const REGISTRY_PREFIX = 'manager://user';
    const USER_SESSION_BUCKET = 'user';
    const CLIENT_SESSION_KEY = 'Client';

    public $session;

    private $_accessLockCache = [];

    protected function __construct() {
        $this->session = new user\session\Controller();
    }

// Client
    public function getClient() {
        if(!isset($this->client)) {
            $this->_loadClient();
        }

        return $this->client;
    }

    protected function _loadClient() {
        $bucket = $this->session->getBucket(self::USER_SESSION_BUCKET);
        $this->client = $bucket->get(self::CLIENT_SESSION_KEY);
        $regenKeyring = false;
        $isNew = false;

        if($this->client === null) {
            $this->client = Client::generateGuest($this);
            $regenKeyring = true;
            $rethrowException = false;
            $isNew = true;
        } else {
            $cache = user\session\Cache::getInstance();
            $regenKeyring = $cache->shouldRegenerateKeyring($this->client->getKeyringTimestamp());
            $rethrowException = false;
            core\i18n\Manager::getInstance()->setLocale($this->client->getLanguage().'_'.$this->client->getCountry());
        }

        if(!$this->client->isLoggedIn() && $this->_recallIdentity($isNew)) {
            $regenKeyring = false;
        }

        if($regenKeyring) {
            try {
                if($this->client->isLoggedIn()) {
                    $this->refreshClientData();
                } else {
                    $this->client->setKeyring(
                        $this->getUserModel()->generateKeyring($this->client)
                    );

                    mesh\Manager::getInstance()->emitEvent($this->client, 'initiate');

                    $bucket->set(self::CLIENT_SESSION_KEY, $this->client);
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
        unset($this->client);
        $this->_accessLockCache = [];
        return $this;
    }

    public function isA($signifier) {
        return $this->client->isA(func_get_args());
    }

    public function canAccess($lock, $action=null, $linkTo=false) {
        if(!$lock instanceof IAccessLock) {
            $lock = $this->getAccessLock($lock);
        }

        return $this->client->canAccess($lock, $action, $linkTo);
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
        } else if(substr($lock, 0, 12) == 'directory://'
              || (is_string($lock) && false === strpos($lock, '://'))) {
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

    public function getSessionNamespace($name) {
        return $this->session->getBucket($name);
    }

    public function getSessionStartTime() {
        return $this->session->getDescriptor()->startTime;
    }



// Options
    public function setClientOption($key, $value) {
        return $this->setClientOptions([$key => $value]);
    }

    public function getClientOption($key, $default=null) {
        $this->_ensureClientOptions();
        return $this->client->getOption($key, $default);
    }

    public function setClientOptions(array $options) {
        $this->_ensureClientOptions();
        $this->getUserModel()->updateClientOptions($this->client->getId(), $options);

        $options = array_merge($this->client->getOptions(), $options);
        $this->client->importOptions($options);

        $bucket = $this->session->getBucket(self::USER_SESSION_BUCKET);
        $bucket->set(self::CLIENT_SESSION_KEY, $this->client);

        return $this;
    }

    public function getClientOptions() {
        $this->_ensureClientOptions();
        return $this->client->getOptions();
    }

    private function _ensureClientOptions() {
        if($this->client->hasOptions()) {
            return;
        }

        $options = $this->getUserModel()->fetchClientOptions($this->client->getId());

        if(!is_array($options)) {
            $options = [];
        }

        $this->client->importOptions($options);
    }




// Authentication
    public function isLoggedIn() {
        return $this->client->isLoggedIn();
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
            $bucket = $this->session->getBucket(self::USER_SESSION_BUCKET);
            $this->_accessLockCache = [];

            // Import user data
            $domainInfo->onAuthentication();
            $clientData = $domainInfo->getClientData();

            if(!$clientData instanceof user\IClientDataObject) {
                throw new AuthenticationException(
                    'Domain info could not provide a valid client data object'
                );
            }

            $this->client->import($clientData);

            // Set state
            $this->client->setAuthenticationState(IState::CONFIRMED);
            $this->client->setKeyring($model->generateKeyring($this->client));
            $this->session->setUserId($clientData->getId());

            $clientData->onAuthentication($this->client);


            // Remember me
            if($request->getAttribute('rememberMe')) {
                $this->session->perpetuateRecall($this->client);
            }

            // Options
            $this->_ensureClientOptions();

            // Trigger hook
            mesh\Manager::getInstance()->emitEvent($this->client, 'authenticate', [
                'request' => $request,
                'result' => $result
            ]);

            // Store session
            $bucket->set(self::CLIENT_SESSION_KEY, $this->client);
        }

        return $result;
    }

    public function authenticateRecallKey(user\session\RecallKey $key) {
        if(!$this->session->hasRecallKey($key)) {
            return false;
        }

        $bucket = $this->session->getBucket(self::USER_SESSION_BUCKET);
        $this->_accessLockCache = [];

        $model = $this->getUserModel();
        $clientData = $model->getClientData($key->userId);
        $this->client = Client::factory($clientData);

        // Set state
        $this->client->setAuthenticationState(IState::BOUND);
        $this->client->setKeyring($model->generateKeyring($this->client));
        $this->session->setUserId($clientData->getId());

        $clientData->onAuthentication($this->client);


        // Remember me
        $this->session->perpetuateRecall($this->client, $key);

        // Options
        $this->_ensureClientOptions();

        // Trigger hook
        mesh\Manager::getInstance()->emitEvent($this->client, 'recall', [
            'key' => $key
        ]);

        // Store session
        $bucket->set(self::CLIENT_SESSION_KEY, $this->client);

        return true;
    }

    public function refreshClientData() {
        if($this->isLoggedIn()) {
            $model = $this->getUserModel();
            $data = $model->getClientData($this->client->getId());
            $this->client->import($data);
            $this->_ensureClientOptions();
        }

        $this->regenerateKeyring();
        mesh\Manager::getInstance()->emitEvent($this->client, 'refresh');

        // Save session
        $bucket = $this->session->getBucket(self::USER_SESSION_BUCKET);
        $bucket->set(self::CLIENT_SESSION_KEY, $this->client);

        return $this;
    }

    public function importClientData(user\IClientDataObject $data) {
        if($this->client->getId() != $data->getId()) {
            throw new AuthenticationException(
                'Client data to import is not for the currently authenticated user'
            );
        }

        $this->client->import($data);
        $this->_ensureClientOptions();

        $bucket = $this->session->getBucket(self::USER_SESSION_BUCKET);
        $bucket->set(self::CLIENT_SESSION_KEY, $this->client);

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
        mesh\Manager::getInstance()->emitEvent($this->client, 'logout');
        $this->session->destroy();
        $this->clearClient();
        return $this;
    }

    public function regenerateKeyring() {
        $this->client->setKeyring(
            $this->getUserModel()->generateKeyring($this->client)
        );

        return $this;
    }


    public function instigateGlobalKeyringRegeneration() {
        $cache = user\session\Cache::getInstance();
        $cache->setGlobalKeyringTimestamp();

        return $this;
    }


// Helpers
    public function getHelper($name) {
        $name = lcfirst($name);

        if(!isset($this->{$name})) {
            $this->{$name} = user\helper\Base::factory($this, $name);
        }

        return $this->{$name};
    }

    public function onApplicationShutdown() {
        $obj = new \ReflectionObject($this);
        $props = $obj->getProperties(\ReflectionProperty::IS_PUBLIC);

        foreach($props as $prop) {
            $name = $prop->getName();
            $value = $prop->getValue($this);

            if(!$value instanceof core\IShutdownAware) {
                continue;
            }

            $value->onApplicationShutdown();
        }
    }


// Passwords
    public function analyzePassword($password) {
        return new flex\PasswordAnalyzer($password, df\Launchpad::$application->getPassKey());
    }

    public function __get($member) {
        switch($member) {
            case 'client':
                return $this->getClient();

            case 'session':
                return $this->session;

            default:
                return $this->getHelper($member);
        }
    }

// Dump
    public function getDumpProperties() {
        return [
            'client' => $this->client,
            'session' => $this->session
        ];
    }
}
