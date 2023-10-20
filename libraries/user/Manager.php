<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\user;

use DecodeLabs\Cosmos;
use DecodeLabs\Exceptional;
use DecodeLabs\Glitch;
use df\arch;
use df\axis;

use df\core;
use df\mesh;
use df\user;

class Manager implements IManager, core\IShutdownAware
{
    use core\TManager;
    use mesh\event\TEmitter;

    public const REGISTRY_PREFIX = 'manager://user';
    public const USER_SESSION_BUCKET = 'user';
    public const CLIENT_SESSION_KEY = 'Client';

    private $_accessLockCache = [];

    // Client
    public function getClient()
    {
        if (!isset($this->client)) {
            $this->_loadClient();
        }

        return $this->client;
    }

    protected function _loadClient()
    {
        $bucket = $this->getHelper('session')->getBucket(self::USER_SESSION_BUCKET);
        $this->client = $bucket->get(self::CLIENT_SESSION_KEY);
        $regenKeyring = false;
        $isNew = false;

        if ($this->client === null) {
            $this->client = Client::generateGuest($this);
            $regenKeyring = true;
            $isNew = true;
        } else {
            $cache = user\session\Cache::getInstance();
            $regenKeyring = $cache->shouldRegenerateKeyring($this->client->getKeyringTimestamp());
            core\i18n\Manager::getInstance()->setLocale($this->client->getLanguage() . '_' . $this->client->getCountry());
        }

        if (!$this->client->isLoggedIn() && $this->auth->recallIdentity($isNew)) {
            $regenKeyring = false;
        }


        try {
            Cosmos::setTimezone($this->client->getTimezone());
            Cosmos::setLocale($this->client->getLanguage() . '_' . $this->client->getCountry());
        } catch (\Throwable $e) {
            Glitch::logException($e);
        }


        if ($regenKeyring) {
            try {
                if ($this->client->isLoggedIn()) {
                    $this->refreshClientData();
                } else {
                    $this->client->setKeyring(
                        $this->getUserModel()->generateKeyring($this->client)
                    );

                    mesh\Manager::getInstance()->emitEvent($this->client, 'initiate');
                    $bucket->set(self::CLIENT_SESSION_KEY, $this->client);
                }
            } catch (\Throwable $e) {
                core\logException($e);
            }
        }
    }


    public function storeClient()
    {
        if (isset($this->client)) {
            $bucket = $this->getHelper('session')->getBucket(self::USER_SESSION_BUCKET);
            $bucket->set(self::CLIENT_SESSION_KEY, $this->client);
        }

        return $this;
    }

    public function clearClient()
    {
        unset($this->client);
        $this->clearAccessLockCache();
        return $this;
    }

    public function getId(): ?string
    {
        return $this->client->getId();
    }

    public function isLoggedIn()
    {
        return $this->client->isLoggedIn();
    }

    public function refreshClientData()
    {
        if ($this->isLoggedIn()) {
            $model = $this->getUserModel();
            $data = $model->getClientData($this->client->getId());

            if (!$data) {
                $this->auth->unbind(true);
                $this->client = Client::generateGuest($this);
            } else {
                $this->client->import($data);
            }
        }

        $this->regenerateKeyring();
        mesh\Manager::getInstance()->emitEvent($this->client, 'refresh');

        $this->storeClient();

        if (isset($this->options)) {
            $this->options->refresh();
        }

        return $this;
    }

    public function importClientData(user\IClientDataObject $data)
    {
        if ($this->client->getId() != $data->getId()) {
            throw Exceptional::Authentication(
                'Client data to import is not for the currently authenticated user'
            );
        }

        $this->client->import($data);
        $this->storeClient();

        if (isset($this->options)) {
            $this->options->refresh();
        }

        return $this;
    }

    public function regenerateKeyring()
    {
        $this->client->setKeyring(
            $this->getUserModel()->generateKeyring($this->client)
        );

        return $this;
    }


    public function instigateGlobalKeyringRegeneration()
    {
        $cache = user\session\Cache::getInstance();
        $cache->setGlobalKeyringTimestamp();

        return $this;
    }

    public function isA(...$signifiers)
    {
        return $this->client->isA(...$signifiers);
    }

    public function canAccess($lock, $action = null, $linkTo = false)
    {
        if (!$lock instanceof IAccessLock) {
            $lock = $this->getAccessLock($lock);
        }

        return $this->client->canAccess($lock, $action, $linkTo);
    }

    public function getAccessLock($lock)
    {
        if ($lock instanceof user\IAccessLock) {
            return $lock;
        }

        if (is_bool($lock)) {
            return new user\access\lock\Boolean($lock);
        }

        $lock = $lockId = (string)$lock;

        if (isset($this->_accessLockCache[$lockId])) {
            return $this->_accessLockCache[$lockId];
        }

        if ($lock == '*') {
            $lock = (new user\access\lock\Boolean(true))
                ->setAccessLockDomain('*');
        } elseif (substr($lock, 0, 10) == 'virtual://') {
            $lock = new user\access\lock\Virtual(substr($lock, 10));
        } elseif (
            substr($lock, 0, 12) == 'directory://' ||
            (
                is_string($lock) && false === strpos($lock, '://')
            )
        ) {
            $lock = new arch\Request($lock);
        } else {
            $action = null;

            try {
                $parts = explode('#', $lock);
                $entityId = array_shift($parts);
                $action = array_shift($parts);

                $meshManager = mesh\Manager::getInstance();
                $lock = $meshManager->fetchEntity($entityId);
            } catch (\Throwable $e) {
                $lock = new user\access\lock\Boolean(true);
            }

            if ($action !== null) {
                $lock = $lock->getActionLock($action);
            }
        }

        $this->_accessLockCache[$lockId] = $lock;

        return $lock;
    }

    public function clearAccessLockCache()
    {
        $this->_accessLockCache = [];
        return $this;
    }



    // Helpers
    public function getHelper(string $name)
    {
        $name = lcfirst($name);

        if (!isset($this->{$name})) {
            $this->{$name} = user\helper\Base::factory($this, $name);
        }

        return $this->{$name};
    }

    public function getUserModel(): IUserModel
    {
        $model = axis\Model::factory('user');

        if (!$model instanceof IUserModel) {
            throw Exceptional::Authentication(
                'User model does not implement user\\IUserModel'
            );
        }

        return $model;
    }


    public function __get($member)
    {
        switch ($member) {
            case 'client':
                return $this->getClient();

            default:
                return $this->getHelper($member);
        }
    }


    // Events
    public function emitEventObject(mesh\event\IEvent $event)
    {
        $obj = new \ReflectionObject($this);
        $props = $obj->getProperties(\ReflectionProperty::IS_PUBLIC);

        foreach ($props as $prop) {
            $name = $prop->getName();
            $value = $prop->getValue($this);

            if (!$value instanceof mesh\event\IListener) {
                continue;
            }

            $value->handleEvent($event);
        }

        mesh\Manager::getInstance()->emitEventObject($event);
        return $this;
    }

    public function onAppShutdown(): void
    {
        $obj = new \ReflectionObject($this);
        $props = $obj->getProperties(\ReflectionProperty::IS_PUBLIC);

        foreach ($props as $prop) {
            $name = $prop->getName();
            $value = $prop->getValue($this);

            if (!$value instanceof core\IShutdownAware) {
                continue;
            }

            $value->onAppShutdown();
        }
    }
}
