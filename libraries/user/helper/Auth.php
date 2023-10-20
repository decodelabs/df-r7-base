<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\user\helper;

use DecodeLabs\Exceptional;
use DecodeLabs\R7\Config\Authentication as AuthConfig;
use df\core;
use df\user;

class Auth extends Base
{
    public function isBound()
    {
        return $this->manager->client->isLoggedIn();
    }

    public function loadAdapter($name)
    {
        $class = 'df\\user\\authentication\\adapter\\' . ucfirst($name);

        if (!class_exists($class)) {
            throw Exceptional::{'df/user/Authentication'}(
                'Authentication adapter ' . $name . ' could not be found'
            );
        }

        return new $class($this->manager);
    }

    public function newRequest($adapter)
    {
        return new user\authentication\Request($adapter);
    }

    public function bind(user\authentication\IRequest $request)
    {
        $timer = new core\time\Timer();

        // Get adapter
        $manager = $this->manager;
        $name = $request->getAdapterName();
        $adapter = $this->loadAdapter($name);

        $config = AuthConfig::load();

        if (!$config->isAdapterEnabled($name)) {
            throw Exceptional::{'df/user/Authentication'}(
                'Authentication adapter ' . $name . ' is not enabled'
            );
        }

        $model = $this->manager->getUserModel();

        // Fetch user
        $result = new user\authentication\Result($name);
        $result->setIdentity($request->getIdentity());

        // Authenticate
        $adapter->authenticate($request, $result);

        if ($result->isValid()) {
            $domainInfo = $result->getDomainInfo();
            $bucket = $manager->session->getBucket($manager::USER_SESSION_BUCKET);
            $manager->clearAccessLockCache();

            // Import user data
            $domainInfo->onAuthentication();
            $clientData = $domainInfo->getClientData();

            if (!$clientData instanceof user\IClientDataObject) {
                throw Exceptional::{'df/user/Authentication'}(
                    'Domain info could not provide a valid client data object'
                );
            }

            if ($clientData->getStatus() !== user\IState::CONFIRMED) {
                $result->setCode($result::NO_STATUS);
                return $result;
            }

            $manager->client->import($clientData);

            // Set state
            $manager->client->setAuthenticationState(user\IState::CONFIRMED);
            $manager->client->setKeyring($model->generateKeyring($manager->client));
            $manager->session->setUserId($clientData->getId());

            if ($clientData instanceof user\IActiveClientDataObject) {
                $clientData->onAuthentication($manager->client);
            }

            // Store session
            $bucket->set($manager::CLIENT_SESSION_KEY, $manager->client);


            // Remember me
            if ($request->getAttribute('rememberMe')) {
                $manager->session->perpetuateRecall($manager->client);
            }

            // Trigger hook
            $manager->emitEvent($manager->client, 'bind');

            $manager->emitEvent($manager->client, 'authenticate', [
                'request' => $request,
                'result' => $result
            ]);
        }

        return $result;
    }



    // Access key
    public function bindDirect($userId, bool $asAdmin = false)
    {
        $manager = $this->manager;
        $bucket = $manager->session->getBucket($manager::USER_SESSION_BUCKET);
        $manager->clearAccessLockCache();
        $model = $manager->getUserModel();

        // Get client data
        if (!$clientData = $model->getClientData($userId)) {
            return false;
        }

        // Check status
        if ($clientData->getStatus() !== user\IState::CONFIRMED) {
            return false;
        }

        // Set client
        $manager->client = user\Client::factory($clientData);

        // Set state
        $manager->client->setAuthenticationState(
            $asAdmin ?
                user\IState::CONFIRMED :
                user\IState::BOUND
        );

        $manager->client->setKeyring($model->generateKeyring($manager->client));
        $manager->session->setUserId($clientData->getId());

        $clientData->onAuthentication($manager->client, $asAdmin);

        // Store session
        $bucket->set($manager::CLIENT_SESSION_KEY, $manager->client);

        // Trigger hook
        $manager->emitEvent($manager->client, 'bind');

        return true;
    }


    // Recall
    public function bindRecallKey(user\session\RecallKey $key)
    {
        $manager = $this->manager;

        // Check key
        if (!$manager->session->hasRecallKey($key)) {
            return false;
        }

        // Bind
        if (!$this->bindDirect($key->userId)) {
            return false;
        }

        // Remember me
        $manager->session->perpetuateRecall($manager->client, $key);

        // Trigger hook
        $manager->emitEvent($manager->client, 'recall', [
            'key' => $key
        ]);

        return true;
    }



    public function recallIdentity($isNew)
    {
        if ($key = $this->manager->session->perpetuator->getRecallKey($this->manager->session)) {
            if ($this->bindRecallKey($key)) {
                return true;
            } else {
                try {
                    $this->manager->session->perpetuator->destroyRecallKey($this->manager->session);
                } catch (\Throwable $e) {
                    core\logException($e);
                }
            }
        }

        $canRecall = $this->manager->session->perpetuator->canRecallIdentity();

        if ($canRecall) {
            $config = AuthConfig::load();

            foreach ($config->getEnabledAdapters() as $name => $options) {
                try {
                    $adapter = $this->loadAdapter($name);
                } catch (user\AuthenticationException $e) {
                    continue;
                }

                if (!$adapter instanceof user\authentication\IIdentityRecallAdapter) {
                    continue;
                }

                $request = $adapter->recallIdentity();

                if (!$request instanceof user\authentication\IRequest) {
                    continue;
                }

                if ($this->bind($request)) {
                    return true;
                }
            }
        }

        return false;
    }



    // Unbind
    public function unbind(bool $restartSession = false)
    {
        $this->manager->emitEvent($this->manager->client, 'logout');
        $this->manager->session->destroy($restartSession);

        return $this;
    }
}
