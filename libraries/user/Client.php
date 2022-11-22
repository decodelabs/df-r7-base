<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\user;

use DateTime;
use DecodeLabs\Dictum;
use DecodeLabs\Disciple;
use DecodeLabs\Exceptional;
use DecodeLabs\Genesis;

use df\core;
use df\flex;
use df\link;
use df\mesh;
use df\user;

class Client implements IClient, \Serializable, mesh\entity\IEntity
{
    use TNameExtractor;

    protected $_id;
    protected $_email;
    protected $_fullName;
    protected $_nickName;
    protected $_joinDate;
    protected $_loginDate;
    protected $_country;
    protected $_language = 'en';
    protected $_timezone;

    protected $_groupIds = [];
    protected $_signifiers = [];

    protected $_authState = IState::GUEST;
    protected $_keyring = [];
    protected $_keyringTimestamp;

    private $_accessCache = [];

    public static function stateIdToName($state)
    {
        if ($state === null) {
            return 'None';
        }

        switch ((int)$state) {
            case IState::SPAM:
                return 'Spam';

            case IState::DEACTIVATED:
                return 'Deactivated';

            case IState::GUEST:
                return 'Guest';

            case IState::PENDING:
                return 'Pending';

            case IState::BOUND:
                return 'Bound';

            case IState::CONFIRMED:
                return 'Confirmed';

            default:
                return null;
        }
    }

    public static function factory(IClientDataObject $data)
    {
        $output = new self();
        $output->import($data);
        return $output;
    }

    public function getEntityLocator()
    {
        return new mesh\entity\Locator('user://Client');
    }

    public static function generateGuest(user\IManager $manager)
    {
        $output = new self();
        $output->_id = null;
        $output->_email = null;
        $output->_fullName = 'Guest';
        $output->_nickName = 'Guest';
        $output->_joinDate = null;
        $output->_loginDate = null;

        $i18nManager = core\i18n\Manager::getInstance();
        $locale = $i18nManager->getLocale();

        $output->_language = $locale->getLanguage();

        $ip = Disciple::getIp();
        $geoIp = link\geoIp\Handler::factory()->lookup($ip);

        if ($geoIp->country) {
            $output->_country = $geoIp->country;
        } else {
            $output->_country = $locale->getRegion();
        }

        if ($geoIp->timezone) {
            $output->_timezone = $geoIp->timezone;
        }

        if ($output->_country === null) {
            $output->_country = $locale->getRegion();
        }

        if ($output->_country === null) {
            $output->_country = $i18nManager->countries->suggestCountryForLanguage($output->_language);
        }

        if ($output->_timezone === null) {
            $output->_timezone = $i18nManager->timezones->suggestForCountry($output->_country);
        }

        $output->_authState = IState::GUEST;
        $output->_groupIds = [];
        $output->_signifiers = ['guest'];

        return $output;
    }

    public function serialize()
    {
        return json_encode($this->__serialize());
    }

    public function __serialize(): array
    {
        return [
            'id' => $this->_id,
            'em' => $this->_email,
            'fn' => $this->_fullName,
            'nn' => $this->_nickName,
            'jd' => Dictum::$time->format($this->_joinDate, core\time\Date::DBDATE, 'UTC'),
            'ld' => Dictum::$time->format($this->_loginDate, core\time\Date::DB, 'UTC'),
            'cn' => $this->_country,
            'ln' => $this->_language,
            'tz' => $this->_timezone,
            'gr' => $this->_groupIds,
            'si' => $this->_signifiers,
            'as' => $this->_authState,
            'kr' => $this->_keyring,
            'kt' => $this->_keyringTimestamp
        ];
    }

    public function unserialize(string $data): void
    {
        $data = json_decode($data, true);
        $this->__unserialize($data);
    }

    public function __unserialize(array $data): void
    {
        $this->_id = $data['id'];
        $this->_email = $data['em'];
        $this->_fullName = $data['fn'];
        $this->_nickName = $data['nn'];

        if ($data['jd']) {
            $this->_joinDate = new DateTime($data['jd']);
        }

        if ($data['ld']) {
            $this->_loginDate = new DateTime($data['ld']);
        }

        $this->_country = $data['cn'];
        $this->_language = $data['ln'];
        $this->_timezone = $data['tz'];

        if (isset($data['gr'])) {
            $this->_groupIds = (array)$data['gr'];
        }

        if (isset($data['si'])) {
            $this->_signifiers = (array)$data['si'];
        }

        $this->_authState = $data['as'];
        $this->_keyring = $data['kr'];
        $this->_keyringTimestamp = $data['kt'];
    }

    public function getId(): ?string
    {
        return $this->_id;
    }

    public function getEmail(): ?string
    {
        return $this->_email;
    }

    public function getFullName(): ?string
    {
        return $this->_fullName;
    }

    public function getNickName(): ?string
    {
        return $this->_nickName;
    }

    public function getStatus()
    {
        return $this->_authState;
    }

    public function getRegistrationDate(): ?DateTime
    {
        return $this->_joinDate;
    }

    public function getLastLoginDate(): ?DateTime
    {
        return $this->_loginDate;
    }

    public function getLanguage(): ?string
    {
        return $this->_language;
    }

    public function getCountry(): ?string
    {
        return $this->_country;
    }

    public function getTimezone(): ?string
    {
        return $this->_timezone;
    }

    public function getGroupIds()
    {
        return $this->_groupIds;
    }

    public function getSignifiers(): array
    {
        return $this->_signifiers;
    }


    public function setAuthenticationState($state)
    {
        switch ($state) {
            case IState::GUEST:
            case IState::PENDING:
            case IState::BOUND:
            case IState::CONFIRMED:
                $this->_authState = $state;
                break;

            default:
                $this->_authState = IState::GUEST;
                break;
        }

        $this->_accessCache = [];

        return $this;
    }

    public function getAuthenticationState()
    {
        return $this->_authState;
    }

    public function isDeactivated(): bool
    {
        return $this->_authState < 0;
    }

    public function isSpam(): bool
    {
        return $this->_authState === IState::SPAM;
    }

    public function isGuest(): bool
    {
        return $this->_authState == IState::GUEST;
    }

    public function isPending(): bool
    {
        return $this->_authState == IState::PENDING;
    }

    public function isLoggedIn(): bool
    {
        return $this->_id !== null;
    }

    public function isBound(): bool
    {
        return $this->_authState == IState::BOUND;
    }

    public function isConfirmed(): bool
    {
        return $this->_authState >= IState::CONFIRMED;
    }

    public function isA(...$signifiers): bool
    {
        foreach ($signifiers as $signifier) {
            if (in_array($signifier, $this->_signifiers)) {
                return true;
            }
        }

        return false;
    }



    public function import(IClientDataObject $clientData)
    {
        $this->_accessCache = [];

        $this->_id = $clientData->getId();
        $this->_email = $clientData->getEmail();
        $this->_fullName = $clientData->getFullName();
        $this->_nickName = $clientData->getNickName();
        $this->_authState = $clientData->getStatus();
        $this->_joinDate = $clientData->getRegistrationDate();
        $this->_loginDate = $clientData->getLastLoginDate();
        $this->_language = $clientData->getLanguage();
        $this->_country = $clientData->getCountry();
        $this->_timezone = $clientData->getTimezone();
        $this->_groupIds = [];

        foreach ($clientData->getGroupIds() as $groupId) {
            $this->_groupIds[] = (string)flex\Guid::factory($groupId);
        }

        $this->_signifiers = $clientData->getSignifiers();

        $ip = Disciple::getIp();
        $geoIp = link\geoIp\Handler::factory()->lookup($ip);

        if ($geoIp->country) {
            $this->_country = $geoIp->country;
        }

        if ($geoIp->timezone) {
            $this->_timezone = $geoIp->timezone;
        }
    }

    public function setKeyring(array $keyring)
    {
        $this->_accessCache = [];
        $this->_keyring = $keyring;
        $this->_keyringTimestamp = time();

        return $this;
    }

    public function getKeyring()
    {
        return $this->_keyring;
    }

    public function getKeyringTimestamp()
    {
        return $this->_keyringTimestamp;
    }


    public function canAccess(IAccessLock $lock, $action = null, $linkTo = false)
    {
        $domain = $lock->getAccessLockDomain();

        if ($domain == 'dynamic') {
            return $lock->getDefaultAccess($action);
        }

        $lockId = $domain . '://' . $lock->getAccessLockId();

        if ($action !== null) {
            $lockId .= '#' . $action;
        }

        if (array_key_exists($lockId, $this->_accessCache)) {
            return $this->_accessCache[$lockId];
        }

        $output = null;

        if (isset($this->_keyring[$domain])) {
            $output = $lock->lookupAccessKey($this->_keyring[$domain], $action);

            if (!$output && isset($this->_keyring[$domain]['*'])) {
                $output = $this->_keyring[$domain]['*'];
            }
        } elseif (isset($this->_keyring['*'])) {
            $output = $lock->lookupAccessKey($this->_keyring['*'], $action);
        }

        if (!$output && isset($this->_keyring['*']['*'])) {
            $output = $this->_keyring['*']['*'];
        }

        if ($output === null) {
            $default = $lock->getDefaultAccess($action);

            if ($default === true || $default === false) {
                $output = $default;
            } else {
                switch ($default) {
                    case IState::SPAM:
                    case IState::DEACTIVATED:
                        $output = $this->isDeactivated();
                        break;

                    case IState::GUEST:
                        $output = $this->_authState >= IState::GUEST;
                        break;

                    case IState::PENDING:
                        $output = $this->isPending();
                        break;

                    case IState::BOUND:
                        $output = $this->isLoggedIn();
                        break;

                    case IState::CONFIRMED:
                        if ($linkTo) {
                            $output = $this->isLoggedIn();
                        } else {
                            $output = $this->isConfirmed();
                        }

                        break;

                    case IState::DEV:
                        $output = Genesis::$environment->isDevelopment()
                                && $this->_authState >= IState::GUEST
                                && $this->_authState != IState::PENDING;
                        break;

                    default:
                        $output = false;
                        break;
                }
            }
        }

        if ($output
        && !isset($this->_keyring['*']['*'])
        && !isset($this->_keyring[$domain]['*'])
        && !empty($signifiers = $lock->getAccessSignifiers())
        && !$this->isA(...$signifiers)) {
            $output = false;
        }

        $this->_accessCache[$lockId] = $output;

        return $output;
    }




    // Array access
    public function offsetSet(
        mixed $key,
        mixed $value
    ): void {
        throw Exceptional::Runtime(
            'Client objects are read only'
        );
    }

    /**
     * @param string $key
     */
    public function offsetGet(mixed $key): mixed
    {
        switch ($key) {
            case 'id': return $this->_id;
            case 'email': return $this->_email;
            case 'fullName': return $this->_fullName;
            case 'nickName': return $this->_nickName;
            case 'firstName': return $this->getFirstName();
            case 'surname': return $this->getSurname();
            case 'state': return $this->_authState;
            case 'joinDate': return $this->_joinDate;
            case 'loginDate': return $this->_loginDate;
            case 'language': return $this->_language;
            case 'country': return $this->_country;
            case 'timezone': return $this->_timezone;
        }

        return null;
    }

    public function offsetExists(mixed $key): bool
    {
        return in_array($key, [
            'id', 'email', 'fullName', 'nickName', 'firstName', 'surname',
            'state', 'joinDate', 'loginDate', 'language', 'country', 'timezone'
        ]);
    }

    public function offsetUnset(mixed $key): void
    {
        throw Exceptional::Runtime(
            'Client objects are read only'
        );
    }
}
