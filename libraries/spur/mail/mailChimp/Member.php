<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\mail\mailChimp;

use df;
use df\core;
use df\spur;
use df\halo;

class Member implements IMember, core\IDumpable {

    protected $_listId;
    protected $_email;
    protected $_id;
    protected $_webId;
    protected $_emailType;
    protected $_signupIp;
    protected $_signupTimestamp;
    protected $_optinIp;
    protected $_optinTimestamp;
    protected $_creationTimestamp;
    protected $_updateTimestamp;
    protected $_memberRating = 0;
    protected $_language = 'en';
    protected $_status;
    protected $_isGoldenMonkey = false;
    protected $_merges = array();
    protected $_groupSets = array();
    protected $_lists = array();
    protected $_geo = array();
    protected $_clients = array();
    protected $_staticSegments = array();
    protected $_notes = array();
    protected $_mediator;

    public function __construct(IMediator $mediator, $listId, array $apiData) {
        $this->_mediator = $mediator;
        $this->_listId = $listId;

        $this->_email = $apiData['email'];
        $this->_id = $apiData['id'];
        $this->_webId = $apiData['web_id'];
        $this->_emailType = $apiData['email_type'];
        $this->_signupIp = $apiData['ip_signup'];
        $this->_signupTimestamp = $apiData['timestamp_signup'];
        $this->_optinIp = $apiData['ip_opt'];
        $this->_optinTimestamp = $apiData['timestamp_opt'];
        $this->_creationTimestamp = $apiData['timestamp'];
        $this->_updateTimestamp = $apiData['info_changed'];
        $this->_memberRating = $apiData['member_rating'];
        $this->_language = $apiData['language'];
        $this->_status = $apiData['status'];
        $this->_isGoldenMonkey = $apiData['is_gmonkey'];
        $this->_merges = $apiData['merges'];
        $this->_lists = $apiData['lists'];
        $this->_geo = $apiData['geo'];
        $this->_clients = $apiData['clients'];
        $this->_staticSegments = $apiData['static_segments'];
        $this->_notes = $apiData['notes'];

        if(isset($this->_merges['GROUPINGS'])) {
            foreach($this->_merges['GROUPINGS'] as $set) {
                $this->_groupSets[$set['id']] = array_map(
                    function($groupName) {
                        return trim(str_replace('\\,', ',', $groupName));
                    }, 
                    preg_split('/(?<!\\\\)\,\W*/i', $set['groups'])
                );
            }

            unset($this->_merges['GROUPINGS']);
        }
    }

    public function getMediator() {
        return $this->_mediator;
    }

    public function getListId() {
        return $this->_listId;
    }

    public function getEmailAddress() {
        return new core\mail\Address($this->_email);
    }

    public function getId() {
        return $this->_id;
    }

    public function getWebId() {
        return $this->_webId;
    }

    public function getEmailType() {
        return $this->_emailType;
    }

    public function getSignupIp() {
        return new halo\Ip($this->_signupIp);
    }

    public function getSignupTimestamp() {
        return new core\time\Date($this->_signupTimestamp);
    }

    public function getOptinIp() {
        return new halo\Ip($this->_optinIp);
    }

    public function getOptinTimestamp() {
        return new core\time\Date($this->_optinTimestamp);
    }

    public function getCreationTimestamp() {
        return new core\time\Date($this->_creationTimestamp);
    }

    public function getUpdateTimestamp() {
        return new core\time\Date($this->_updateTimestamp);
    }

    public function getMemberRating() {
        return $this->_memberRating;
    }

    public function getLanguage() {
        return $this->_language;
    }

    public function getStatus() {
        return $this->_status;
    }

    public function isGoldenMonkey() {
        return $this->_isGoldenMonkey;
    }

    public function getMergeData() {
        return $this->_merges;
    }

    public function getListData() {
        return $this->_lists;
    }

    public function getGeoData() {
        return $this->_geo;
    }

    public function getClientData() {
        return $this->_clients;
    }

    public function getStaticSegmentData() {
        return $this->_staticSegments;
    }

    public function getNotes() {
        return $this->_notes;
    }

    public function getGroupSetData() {
        return $this->_groupSets;
    }


// Dump
    public function getDumpProperties() {
        $output = [
            'list' => $this->_listId,
            'email' => $this->getEmailAddress(),
            'id' => $this->_id,
            'webId' => $this->_webId,
            'emailType' => $this->_emailType,
            'signupIp' => $this->getSignupIp(),
            'signupTimestamp' => $this->getSignupTimestamp(),
            'optinIp' => $this->getOptinIp(),
            'optinTimestamp' => $this->getOptinTimestamp(),
            'creationTimestamp' => $this->getCreationTimestamp(),
            'updateTimestamp' => $this->getUpdateTimestamp(),
            'memberRating' => $this->_memberRating,
            'language' => $this->_language,
            'status' => $this->_status,
            'goldenMonkey' => $this->_isGoldenMonkey,
            'mergeData' => $this->_merges,
            'groupSetData' => $this->_groupSets
        ];

        if(!empty($this->_lists)) {
            $output['listData'] = $this->_lists;
        }

        if(!empty($this->_geo)) {
            $output['geoData']  = $this->_geo;
        }

        if(!empty($this->_clients)) {
            $output['clientData'] = $this->_clients;
        }

        if(!empty($this->_staticSegments)) {
            $output['staticSegmentData'] = $this->_staticSegments;
        }

        if(!empty($this->_notes)) {
            $output['notes'] = $this->_notes;
        }

        return $output;
    }
}