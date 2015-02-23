<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\user;

use df;
use df\core;
use df\user;
use df\link;
use df\mesh;

class Client implements IClient, \Serializable, mesh\entity\IEntity {
    
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
    protected $_options = null;
    
    protected $_authState = IState::GUEST;
    protected $_keyring = [];
    protected $_keyringTimestamp;

    private $_accessCache = [];

    public static function stateIdToName($state) {
        if($state === null) {
            return 'None';
        }

        switch((int)$state) {
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
    
    public static function factory(IClientDataObject $data) {
        $output = new self();
        $output->import($data);
        return $output;
    }

    public function getEntityLocator() {
        return new mesh\entity\Locator('user://Client');
    }

    public static function generateGuest(user\IManager $manager) {
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

        if(df\Launchpad::$application instanceof core\application\Http) {
            $ip = df\Launchpad::$application->getHttpRequest()->getIp();
            $geoIp = link\geoIp\Handler::factory()->lookup($ip);

            if($geoIp->country) {
                $output->_country = $geoIp->country;
            } else {
                $output->_country = $locale->getRegion();
            }

            if($geoIp->timezone) {
                $output->_timezone = $geoIp->timezone;
            }
        }
        
        if($output->_country === null) {
            $output->_country = $locale->getRegion();
        }
        
        if($output->_country === null) {
            $output->_country = $i18nManager->countries->suggestCountryForLanguage($output->_language);
        }
        
        if($output->_timezone === null) {
            $output->_timezone = $i18nManager->timezones->suggestForCountry($output->_country);
        }

        $output->_authState = IState::GUEST;
        $output->_groupIds = [];
        $output->_options = [];

        return $output;
    }

    public function serialize() {
        return json_encode([
            'id' => $this->_id,
            'em' => $this->_email,
            'fn' => $this->_fullName,
            'nn' => $this->_nickName,
            'jd' => $this->_joinDate ? $this->_joinDate->format(core\time\Date::DBDATE) : null,
            'ld' => $this->_loginDate ? (string)$this->_loginDate : null,
            'cn' => $this->_country,
            'ln' => $this->_language,
            'tz' => $this->_timezone,
            'gr' => $this->_groupIds,
            'op' => $this->_options,
            'as' => $this->_authState,
            'kr' => $this->_keyring,
            'kt' => $this->_keyringTimestamp
        ]);
    }

    public function unserialize($data) {
        $data = json_decode($data, true);
        $this->_id = $data['id'];
        $this->_email = $data['em'];
        $this->_fullName = $data['fn'];
        $this->_nickName = $data['nn'];

        if($data['jd']) {
            $this->_joinDate = new core\time\Date($data['jd']);
        }

        if($data['ld']) {
            $this->_loginDate = new core\time\Date($data['ld']);
        }

        $this->_country = $data['cn'];
        $this->_language = $data['ln'];
        $this->_timezone = $data['tz'];

        if(isset($data['gr'])) {
            $this->_groupIds = (array)$data['gr'];
        }

        if(isset($data['op'])) {
            $this->_options = $data['op'];
        }

        $this->_authState = $data['as'];
        $this->_keyring = $data['kr'];
        $this->_keyringTimestamp = $data['kt'];
    }
    
    public function getId() {
        return $this->_id;
    }
    
    public function getEmail() {
        return $this->_email;
    }
    
    public function getFullName() {
        return $this->_fullName;
    }
    
    public function getNickName() {
        return $this->_nickName;
    }

    public function getStatus() {
        return $this->_authState;
    }
    
    public function getJoinDate() {
        return $this->_joinDate;
    }
    
    public function getLoginDate() {
        return $this->_loginDate;
    }
    
    public function getLanguage() {
        return $this->_language;
    }
    
    public function getCountry() {
        return $this->_country;
    }
    
    public function getTimezone() {
        return $this->_timezone;
    }

    public function getGroupIds() {
        return $this->_groupIds;
    }
    
    
    public function setAuthenticationState($state) {
        switch($state) {
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
    
    public function getAuthenticationState() {
        return $this->_authState;
    }
    
    public function isDeactivated() {
        return $this->_authState == IState::DEACTIVATED;
    }
    
    public function isGuest() {
        return $this->_authState == IState::GUEST;
    }
    
    public function isPending() {
        return $this->_authState == IState::PENDING;
    }
    
    public function isLoggedIn() {
        return $this->_id !== null;
    }
    
    public function isBound() {
        return $this->_authState == IState::BOUND;
    }
    
    public function isConfirmed() {
        return $this->_authState >= IState::CONFIRMED;
    }
    
    
    
    public function import(IClientDataObject $clientData) {
        $this->_accessCache = [];

        $this->_id = $clientData->getId();
        $this->_email = $clientData->getEmail();
        $this->_fullName = $clientData->getFullName();
        $this->_nickName = $clientData->getNickName();
        $this->_authState = $clientData->getStatus();
        $this->_joinDate = $clientData->getJoinDate();
        $this->_loginDate = $clientData->getLoginDate();
        $this->_language = $clientData->getLanguage();
        $this->_country = $clientData->getCountry();
        $this->_timezone = $clientData->getTimezone();
        $this->_groupIds = $clientData->getGroupIds();
        $this->_options = null;

        if(df\Launchpad::$application instanceof core\application\Http) {
            $ip = df\Launchpad::$application->getHttpRequest()->getIp();
            $geoIp = link\geoIp\Handler::factory()->lookup($ip);

            if($geoIp->country) {
                $this->_country = $geoIp->country;
            }

            if($geoIp->timezone) {
                $this->_timezone = $geoIp->timezone;
            }
        }
    }
    
    public function setKeyring(array $keyring) {
        $this->_accessCache = [];
        $this->_keyring = $keyring;
        $this->_keyringTimestamp = time();

        return $this;
    }
    
    public function getKeyring() {
        return $this->_keyring;
    }

    public function getKeyringTimestamp() {
        return $this->_keyringTimestamp;
    }
    
    
    public function canAccess(IAccessLock $lock, $action=null, $linkTo=false) {
        $domain = $lock->getAccessLockDomain();

        if($domain == 'dynamic') {
            return $lock->getDefaultAccess($action);
        }

        $lockId = $domain.'://'.$lock->getAccessLockId();

        if($action !== null) {
            $lockId .= '#'.$action;
        }

        if(array_key_exists($lockId, $this->_accessCache)) {
            return $this->_accessCache[$lockId];
        }

        $output = null;
        
        if(isset($this->_keyring[$domain])) {
            $output = $lock->lookupAccessKey($this->_keyring[$domain], $action);

            if(!$output && isset($this->_keyring[$domain]['*'])) {
                $output = $this->_keyring[$domain]['*'];
            }
        } else if(isset($this->_keyring['*'])) {
            $output = $lock->lookupAccessKey($this->_keyring['*'], $action);
        }

        if(!$output && isset($this->_keyring['*']['*'])) {
            $output = $this->_keyring['*']['*'];
        }
        
        if($output === null) {
            $default = $lock->getDefaultAccess($action);
            
            if($default === true || $default === false) {
                $output = $default;
            } else {
                switch($default) {
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
                        if($linkTo) {
                            $output = $this->isLoggedIn();
                        } else {
                            $output = $this->isConfirmed();
                        }

                        break;
                    
                    case IState::DEV:
                        $output = df\Launchpad::$application->isDevelopment()
                               && $this->_authState >= IState::GUEST
                               && $this->_authState != IState::PENDING;
                        break;
                    
                    default:
                        $output = false;
                        break;
                }
            }
        }

        $this->_accessCache[$lockId] = $output;
        
        return $output;
    }


// Options
    public function hasOptions() {
        return $this->_options !== null;
    }

    public function hasOption($key) {
        return $this->_options !== null 
            && array_key_exists($key, $this->_options);
    }

    public function getOption($key, $default=null) {
        if($this->hasOption($key)) {
            return $this->_options[$key];
        }

        return $default;
    }

    public function getOptions() {
        return $this->_options;
    }

    public function importOptions(array $options) {
        $this->_options = $options;
        return $this;
    }


// Array access
    public function offsetSet($key, $value) {
        throw new RuntimeException('Client objects are read only');
    }

    public function offsetGet($key) {
        switch($key) {
            case 'id': return $this->_id;
            case 'email': return $this->_email;
            case 'fullName': return $this->_fullName;
            case 'nickName': return $this->_nickName;
            case 'firstName': return $this->getFirstName();
            case 'surname': return $this->getSurname();
            case 'stats': return $this->_authState;
            case 'joinDate': return $this->_joinDate;
            case 'loginDate': return $this->_loginDate;
            case 'language': return $this->_language;
            case 'country': return $this->_country;
            case 'timezone': return $this->_timezone;
        }
    }

    public function offsetExists($key) {
        return in_array($key, [
            'id', 'email', 'fullName', 'nickName', 'firstName', 'surname', 
            'stats', 'joinDate', 'loginDate', 'language', 'country', 'timezone'
        ]);
    }

    public function offsetUnset($key) {
        throw new RuntimeException('Client objects are read only');
    }
}