<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\mail\mailChimp;

use df;
use df\core;
use df\spur;
    
class SubscriberList implements IList, core\IDumpable {
    
    protected $_id;
    protected $_webId;
    protected $_name;
    protected $_creationDate;
    protected $_emailTypeOption = false;
    protected $_useAwesomeBar = true;
    protected $_defaultFromAddress;
    protected $_defaultSubject;
    protected $_defaultLanguage = 'en';
    protected $_listRating = 0;
    protected $_shortSubscribeUrl;
    protected $_longSubscribeUrl;
    protected $_beamerAddress;
    protected $_visibility;
    protected $_stats = array();
    protected $_modules = array();
    protected $_mediator;


    public function __construct(IMediator $mediator, array $apiData) {
        $this->_mediator = $mediator;
        $this->_id = $apiData['id'];
        $this->_webId = $apiData['web_id'];
        $this->_name = $apiData['name'];
        $this->_creationDate = $apiData['date_created'];
        $this->_emailTypeOption = $apiData['email_type_option'];
        $this->_useAwesomeBar = $apiData['use_awesomebar'];
        $this->_defaultFromAddress = core\mail\Address::factory($apiData['default_from_email'], $apiData['default_from_name']);
        $this->_defaultSubject = $apiData['default_subject'];
        $this->_defaultLanguage = $apiData['default_language'];
        $this->_listRating = $apiData['list_rating'];
        $this->_shortSubscribeUrl = $apiData['subscribe_url_short'];
        $this->_longSubscribeUrl = $apiData['subscribe_url_long'];
        $this->_beamerAddress = $apiData['beamer_address'];
        $this->_visibility = $apiData['visibility'];
        $this->_stats = $apiData['stats'];
        $this->_modules = $apiData['modules'];
    }

    public function getMediator() {
        return $this->_mediator;
    }

    public function getId() {
        return $this->_id;
    }

    public function getWebId() {
        return $this->_webId;
    }

    public function getName() {
        return $this->_name;
    }

    public function getCreationDate() {
        return new core\time\Date($this->_creationDate);
    }

    public function hasEmailTypeOption() {
        return (bool)$this->_emailTypeOption;
    }

    public function shouldUseAwesomeBar() {
        return (bool)$this->_useAwesomeBar;
    }

    public function getDefaultFromAddress() {
        return $this->_defaultFromAddress;
    }

    public function getDefaultSubject() {
        return $this->_defaultSubject;
    }

    public function getDefaultLanguage() {
        return $this->_defaultLanguage;
    }

    public function getListRating() {
        return $this->_listRating;
    }

    public function getShortSubscribeUrl() {
        return $this->_shortSubscribeUrl;
    }

    public function getLongSubscribeUrl() {
        return $this->_longSubscribeUrl;
    }

    public function getBeamerAddress() {
        return new core\mail\Address($this->_beamerAddress);
    }

    public function getVisibility() {
        return $this->_visibility;
    }


// Stats
    public function getStats() {
        return $this->_stats;
    }

    public function countMembers() {
        if(isset($this->_stats['member_count'])) {
            return (int)$this->_stats['member_count'];
        } else {
            return 0;
        }
    }

    public function countMembersSinceSend() {
        if(isset($this->_stats['member_count_since_send'])) {
            return (int)$this->_stats['member_count_since_send'];
        } else {
            return 0;
        }
    }

    public function countUnsubscribers() {
        if(isset($this->_stats['unsubscribe_count'])) {
            return (int)$this->_stats['unsubscribe_count'];
        } else {
            return 0;
        }
    }

    public function countUnsubscribersSinceSend() {
        if(isset($this->_stats['unsubscribe_count_since_send'])) {
            return (int)$this->_stats['unsubscribe_count_since_send'];
        } else {
            return 0;
        }
    }

    public function countCleaned() {
        if(isset($this->_stats['cleaned_count'])) {
            return (int)$this->_stats['cleaned_count'];
        } else {
            return 0;
        }
    }

    public function countCleanedSinceSend() {
        if(isset($this->_stats['cleaned_count_since_send'])) {
            return (int)$this->_stats['cleaned_count_since_send'];
        } else {
            return 0;
        }
    }

    public function countCampaigns() {
        if(isset($this->_stats['campaign_count'])) {
            return (int)$this->_stats['campaign_count'];
        } else {
            return 0;
        }
    }

    public function countGroupSets() {
        if(isset($this->_stats['grouping_count'])) {
            return (int)$this->_stats['grouping_count'];
        } else {
            return 0;
        }
    }

    public function countGroups() {
        if(isset($this->_stats['group_count'])) {
            return (int)$this->_stats['group_count'];
        } else {
            return 0;
        }
    }

    public function countMergeVars() {
        if(isset($this->_stats['merge_var_count'])) {
            return (int)$this->_stats['merge_var_count'];
        } else {
            return 0;
        }
    }

    public function getAverageSubscriptionRate() {
        if(isset($this->_stats['avg_sub_rate'])) {
            return $this->_stats['avg_sub_rate'];
        }
    }

    public function getAverageUnsubscriptionRate() {
        if(isset($this->_stats['avg_unsub_rate'])) {
            return $this->_stats['avg_unsub_rate'];
        }
    }

    public function getTargetSubscriptionRate() {
        if(isset($this->_stats['target_sub_rate'])) {
            return $this->_stats['target_sub_rate'];
        }
    }

    public function getOpenRate() {
        if(isset($this->_stats['open_rate'])) {
            return $this->_stats['open_rate'];
        }
    }

    public function getClickRate() {
        if(isset($this->_stats['click_rate'])) {
            return $this->_stats['click_rate'];
        }
    }



// Modules
    public function getModules() {
        return $this->_modules;
    }



// Entry
    public function fetchGroupSets() {
        return $this->_mediator->fetchGroupSets($this->_id);
    }

    public function fetchGroups() {
        return $this->_mediator->fetchGroups($this->_id);
    }

    public function addGroupSet($name, array $groupNames, $type=null) {
        return $this->_mediator->addGroupSet($this->_id, $name, $groupNames, $type);
    }



    public function fetchMember($emailAddress) {
        return $this->_mediator->fetchMember($this->_id, $emailAddress);
    }

    public function fetchMemberSet(array $emailAddresses) {
        return $this->_mediator->fetchMemberSet($this->_id, $emailAddresses);
    }



    public function fetchWebHooks() {
        return $this->_mediator->fetchWebHooks($this->_id);
    }

    public function addWebHook($url, array $actions, array $sources) {
        return $this->_mediator->addWebHook($this->_id, $url, $actions, $sources);
    }

    public function deleteWebHook($url) {
        return $this->_mediator->deleteWebHook($this->_id, $url);
    }



// Dump
    public function getDumpProperties() {
        return [
            'id' => $this->_id,
            'webId' => $this->_webId,
            'name' => $this->_name,
            'creationDate' => $this->getCreationDate(),
            'emailTypeOption' => $this->_emailTypeOption,
            'useAwesomeBar' => $this->_useAwesomeBar,
            'defaultFromAddress' => $this->getDefaultFromAddress(),
            'defaultSubject' => $this->_defaultSubject,
            'defaultLanguage' => $this->_defaultLanguage,
            'listRating' => $this->_listRating,
            'shortSubscribeUrl' => $this->_shortSubscribeUrl,
            'longSubscribeUrl' => $this->_longSubscribeUrl,
            'breamerAddress' => $this->getBeamerAddress(),
            'visibility' => $this->_visibility,
            'stats' => $this->_stats,
            'modules' => $this->_modules
        ];
    }
}