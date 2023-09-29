<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\user;

use DecodeLabs\Disciple\Profile as DiscipleProfile;
use DecodeLabs\Exceptional;
use df\core;

use df\mesh;
use df\user;

// Constants
interface IState
{
    public const SPAM = -2;
    public const DEACTIVATED = -1;
    public const GUEST = 0;
    public const PENDING = 1;
    public const BOUND = 2;
    public const CONFIRMED = 3;

    public const ALL = true;
    public const NONE = false;
    public const DEV = 666;
}



// Interfaces
interface IManager extends core\IManager, mesh\event\IEmitter
{
    // Client
    public function getClient();
    public function clearClient();
    public function getId(): ?string;
    public function isLoggedIn();
    public function refreshClientData();
    public function importClientData(user\IClientDataObject $data);
    public function regenerateKeyring();
    public function instigateGlobalKeyringRegeneration();

    public function isA(...$signifiers);
    public function canAccess($lock, $action = null, $linkTo = false);
    public function getAccessLock($lock);
    public function clearAccessLockCache();

    // Helpers
    public function getHelper(string $name);
}

interface IUserModel
{
    public function getClientData($id);
    public function getClientDataList(array $ids, array $emails = null);
    public function getAuthenticationDomainInfo(user\authentication\IRequest $request);
    public function generateKeyring(IClient $client);

    public function fetchClientOptions($id);
    public function updateClientOptions($id, array $options);
    public function removeClientOptions($id, $keys);
}

interface IClientDataObject extends DiscipleProfile, \ArrayAccess
{
    public function getStatus();
    public function getGroupIds();
}

trait TNameExtractor
{
    public function getFirstName(): ?string
    {
        static $titles = ['mr', 'mrs', 'miss', 'ms', 'mx', 'master', 'maid', 'madam', 'dr'];
        $fullName = trim($this->getFullName());

        if (false === ($parts = preg_split('/\s+|\./', $fullName))) {
            $parts = explode(' ', $fullName);
        }

        do {
            $output = (string)array_shift($parts);
            $test = strtolower(str_replace([',', '.', '-'], '', $output));
        } while (count($parts) > 1 && in_array($test, $titles));

        return ucfirst($output);
    }

    public function getSurname(): ?string
    {
        $parts = explode(' ', $this->getFullName());
        return array_pop($parts);
    }
}

interface IActiveClientDataObject extends IClientDataObject
{
    public function onAuthentication(IClient $client, bool $asAdmin = false);
}

interface IClient extends IClientDataObject
{
    public function setAuthenticationState($state);
    public function getAuthenticationState();

    public function isDeactivated(): bool;
    public function isSpam(): bool;
    public function isGuest(): bool;
    public function isPending(): bool;
    public function isLoggedIn(): bool;
    public function isBound(): bool;
    public function isConfirmed(): bool;
    public function isA(...$signifiers): bool;

    public function import(IClientDataObject $clientData);
    public function setKeyring(array $keyring);
    public function getKeyring();
    public function getKeyringTimestamp();

    public function canAccess(IAccessLock $lock, $action = null, $linkTo = false);
}


## Helpers
interface IHelper
{
    public function getManager();
    public function getHelperName();
}

interface ISessionBackedHelper extends IHelper, \ArrayAccess, core\IShutdownAware
{
    public function storeSessionData();
}

trait TSessionBackedHelper
{
    protected $_sessionData = null;
    protected $_sessionDataNew = false;
    protected $_sessionDataChanged = false;

    public function offsetSet(
        mixed $key,
        mixed $value
    ): void {
        $this->_ensureSessionData();
        $this->_sessionData[$key] = $value;
        $this->_sessionDataChanged = true;
    }

    public function offsetGet(mixed $key): mixed
    {
        $this->_ensureSessionData();

        if (isset($this->_sessionData[$key])) {
            return $this->_sessionData[$key];
        }

        return null;
    }

    public function offsetExists(mixed $key): bool
    {
        $this->_ensureSessionData();
        return isset($this->_sessionData[$key]);
    }

    public function offsetUnset(mixed $key): void
    {
        $this->_ensureSessionData();
        unset($this->_sessionData[$key]);
        $this->_sessionDataChanged = true;
    }


    protected function _ensureSessionData()
    {
        if (isset($this->_sessionData)) {
            return;
        }

        $manager = $this->manager;
        $bucket = $manager->session->getBucket($manager::USER_SESSION_BUCKET);
        $this->_sessionData = $bucket->get($this->getHelperName());

        if ($this->_sessionData === null) {
            $this->_sessionData = $this->_generateDefaultSessionData();
            $this->_sessionDataNew = true;
        }
    }

    protected function _generateDefaultSessionData()
    {
        return [];
    }

    protected function _destroySessionData()
    {
        $manager = $this->manager;
        $bucket = $manager->session->getBucket($manager::USER_SESSION_BUCKET);
        $bucket->remove($this->getHelperName());
        $this->_sessionData = null;
    }

    public function onAppShutdown(): void
    {
        $this->storeSessionData();
    }

    public function storeSessionData()
    {
        if ($this->_sessionData === null
        || !$this->_sessionDataChanged
        || (empty($this->_sessionData) && $this->_sessionDataNew)) {
            return;
        }

        $manager = $this->manager;
        $bucket = $manager->session->getBucket($manager::USER_SESSION_BUCKET);

        if (empty($this->_sessionData)) {
            $bucket->remove($this->getHelperName());
        } else {
            $bucket->set($this->getHelperName(), $this->_sessionData);
        }

        $this->_sessionDataChanged = false;
    }

    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        $this->_ensureSessionData();
        yield 'values' => $this->_sessionData;
    }
}



## Access
interface IAccessLock
{
    public function getAccessLockDomain();
    public function lookupAccessKey(array $keys, $action = null);
    public function getDefaultAccess($action = null);
    public function getAccessSignifiers(): array;
    public function getActionLock($action);
    public function getAccessLockId();
}

trait TAccessLock
{
    public function getActionLock($node)
    {
        if (!$this instanceof IAccessLock) {
            throw Exceptional::Logic(
                'Lock requester is not an instance of user\\IAccessLock'
            );
        }

        return new user\access\lock\Action($this, $node);
    }

    public function getAccessSignifiers(): array
    {
        return [];
    }
}


interface IAccessControlled
{
    public function shouldCheckAccess(bool $flag = null);
    public function setAccessLocks(array $locks);
    public function addAccessLocks(array $locks);
    public function addAccessLock($lock);
    public function getAccessLocks();
    public function clearAccessLocks();
}


trait TAccessControlled
{
    protected $_checkAccess = false;
    protected $_accessLocks = [];

    public function shouldCheckAccess(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_checkAccess = $flag;
            return $this;
        }

        return (bool)$this->_checkAccess;
    }

    public function setAccessLocks(array $locks)
    {
        $this->_accessLocks = [];
        return $this->addAccessLocks($locks);
    }

    public function addAccessLocks(array $locks)
    {
        foreach ($locks as $lock) {
            $this->addAccessLock($lock);
        }

        return $this;
    }

    public function addAccessLock($lock)
    {
        $this->_accessLocks[] = $lock;
        $this->_checkAccess = true;
        return $this;
    }

    public function getAccessLocks()
    {
        return $this->_accessLocks;
    }

    public function clearAccessLocks()
    {
        $this->_accessLocks = [];
        return $this;
    }
}



interface IPostalAddress extends core\IStringProvider, core\IArrayProvider
{
    public function getPostOfficeBox();
    public function getStreetLine1();
    public function getStreetLine2();
    public function getStreetLine3();
    public function getMainStreetLine();
    public function getExtendedStreetLine();
    public function getFullStreetAddress();
    public function getLocality();
    public function getRegion();
    public function getPostalCode();
    public function getCountryCode();
    public function getCountryName();
    public function toOneLineString();
}

trait TPostalAddress
{
    protected $_countryName;

    public function getMainStreetLine()
    {
        $output = $this->getStreetLine1();

        if ($this->getStreetLine3() && $t = $this->getStreetLine2()) {
            $output .= ', ' . $t;
        }

        return $output;
    }

    public function getExtendedStreetLine()
    {
        if ($output = $this->getStreetLine3()) {
            return $output;
        }

        return $this->getStreetLine2();
    }

    public function getFullStreetAddress()
    {
        $output = $this->getStreetLine1();
        $address2 = $this->getStreetLine2();
        $address3 = $this->getStreetLine3();

        if (!empty($address2)) {
            $output .= ', ' . $address2;
        }

        if (!empty($address3)) {
            $output .= ', ' . $address3;
        }

        return $output;
    }

    public function getPostOfficeBox()
    {
        foreach ([$this->getStreetLine1(), $this->getStreetLine2(), $this->getStreetLine3()] as $line) {
            if (substr(str_replace('.', ' ', strtolower((string)$line)), 0, 6) == 'po box') {
                return $line;
            }
        }

        return null;
    }

    public function getCountryName()
    {
        if (!$this->_countryName) {
            $this->_countryName = core\i18n\Manager::getInstance()->countries->getName($this->getCountryCode());
        }

        return $this->_countryName;
    }

    public function toString(): string
    {
        $data = $this->toArray();
        $data['country'] = $this->getCountryName();
        $data['countryCode'] = null;

        return implode("\n", array_filter($data, function ($line) {
            return !empty($line);
        }));
    }

    public function toOneLineString()
    {
        return $this->getFullStreetAddress() . ', ' . $this->getLocality() . ', ' . $this->getPostalCode() . ', ' . $this->getCountryCode();
    }

    public function toArray(): array
    {
        return [
            'street1' => $this->getStreetLine1(),
            'street2' => $this->getStreetLine2(),
            'street3' => $this->getStreetLine3(),
            'locality' => $this->getLocality(),
            'region' => $this->getRegion(),
            'postalCode' => $this->getPostalCode(),
            'countryCode' => $this->getCountryCode()
        ];
    }
}
