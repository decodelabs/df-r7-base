<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\user\helper;

use df;
use df\core;
use df\user;

class Auth extends Base {

    public function isBound() {
        return $this->manager->client->isLoggedIn();
    }

    public function loadAdapter($name) {
        $class = 'df\\user\\authentication\\adapter\\'.ucfirst($name);

        if(!class_exists($class)) {
            throw new user\AuthenticationException(
                'Authentication adapter '.$name.' could not be found'
            );
        }

        return new $class($this->manager);
    }

    public function newRequest($adapter) {
        return new user\authentication\Request($adapter);
    }

    public function bind(user\authentication\IRequest $request) {
        $timer = new core\time\Timer();

        // Get adapter
        $manager = $this->manager;
        $name = $request->getAdapterName();
        $adapter = $this->loadAdapter($name);

        $config = user\authentication\Config::getInstance();

        if(!$config->isAdapterEnabled($name)) {
            throw new user\AuthenticationException(
                'Authentication adapter '.$name.' is not enabled'
            );
        }

        $model = $this->manager->getUserModel();

        // Fetch user
        $result = new user\authentication\Result($name);
        $result->setIdentity($request->getIdentity());

        // Authenticate
        $adapter->authenticate($request, $result);

        if($result->isValid()) {
            $domainInfo = $result->getDomainInfo();
            $bucket = $manager->session->getBucket($manager::USER_SESSION_BUCKET);
            $manager->clearAccessLockCache();

            // Import user data
            $domainInfo->onAuthentication();
            $clientData = $domainInfo->getClientData();

            if(!$clientData instanceof user\IClientDataObject) {
                throw new user\AuthenticationException(
                    'Domain info could not provide a valid client data object'
                );
            }

            if($clientData->getStatus() !== user\IState::CONFIRMED) {
                $result->setCode($result::NO_STATUS);
                return $result;
            }

            $manager->client->import($clientData);

            // Set state
            $manager->client->setAuthenticationState(user\IState::CONFIRMED);
            $manager->client->setKeyring($model->generateKeyring($manager->client));
            $manager->session->setUserId($clientData->getId());

            $clientData->onAuthentication($manager->client);


            // Remember me
            if($request->getAttribute('rememberMe')) {
                $manager->session->perpetuateRecall($manager->client);
            }

            // Trigger hook
            $manager->emitEvent($manager->client, 'authenticate', [
                'request' => $request,
                'result' => $result
            ]);

            // Store session
            $bucket->set($manager::CLIENT_SESSION_KEY, $manager->client);
        }

        return $result;
    }

    public function bindRecallKey(user\session\RecallKey $key) {
        $manager = $this->manager;

        if(!$manager->session->hasRecallKey($key)) {
            return false;
        }

        $bucket = $manager->session->getBucket($manager::USER_SESSION_BUCKET);
        $manager->clearAccessLockCache();

        $model = $manager->getUserModel();

        if(!$clientData = $model->getClientData($key->userId)) {
            return false;
        }

        if($clientData->getStatus() !== user\IState::CONFIRMED) {
            return false;
        }

        $manager->client = user\Client::factory($clientData);

        // Set state
        $manager->client->setAuthenticationState(user\IState::BOUND);
        $manager->client->setKeyring($model->generateKeyring($manager->client));
        $manager->session->setUserId($clientData->getId());

        $clientData->onAuthentication($manager->client);


        // Remember me
        $manager->session->perpetuateRecall($manager->client, $key);

        // Trigger hook
        $manager->emitEvent($manager->client, 'recall', [
            'key' => $key
        ]);

        // Store session
        $bucket->set($manager::CLIENT_SESSION_KEY, $manager->client);

        return true;
    }



    public function recallIdentity($isNew) {
        if($key = $this->manager->session->perpetuator->getRecallKey($this->manager->session)) {
            if($this->bindRecallKey($key)) {
                return true;
            }
        }

        $canRecall = $this->manager->session->perpetuator->canRecallIdentity();

        if($canRecall) {
            $config = user\authentication\Config::getInstance();

            foreach($config->getEnabledAdapters() as $name => $options) {
                try {
                    $adapter = $this->loadAdapter($name);
                } catch(user\AuthenticationException $e) {
                    continue;
                }

                if(!$adapter instanceof user\authentication\IIdentityRecallAdapter) {
                    continue;
                }

                $request = $adapter->recallIdentity();

                if(!$request instanceof user\authentication\IRequest) {
                    continue;
                }

                if($this->bind($request)) {
                    return true;
                }
            }
        }

        return false;
    }


    public function unbind(bool $restartSession=false) {
        $this->manager->emitEvent($this->manager->client, 'logout');
        $this->manager->session->destroy($restartSession);

        return $this;
    }
}