<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\mail\mailchimp;

use df;
use df\core;
use df\spur;
use df\flow;
use df\link;

class Member implements IMember, core\IDumpable {

    protected $_listId;
    protected $_email;
    protected $_id;
    protected $_webId;
    protected $_emailType;
    protected $_signupIp;
    protected $_signupDate;
    protected $_optinIp;
    protected $_optinDate;
    protected $_creationDate;
    protected $_updateDate;
    protected $_memberRating = 0;
    protected $_language = 'en';
    protected $_status;
    protected $_isGoldenMonkey = false;
    protected $_merges = [];
    protected $_groupSets = [];
    protected $_lists = [];
    protected $_geo = [];
    protected $_clients = [];
    protected $_staticSegments = [];
    protected $_notes = [];
    protected $_mediator;

    public function __construct(IMediator $mediator, $listId, core\collection\ITree $apiData) {
        $this->_mediator = $mediator;
        $this->_listId = $listId;

        $this->_email = $apiData['email'];
        $this->_id = $apiData['id'];
        $this->_webId = $apiData['web_id'];
        $this->_emailType = $apiData['email_type'];
        $this->_signupIp = $apiData['ip_signup'];
        $this->_signupDate = $apiData['timestamp_signup'];
        $this->_optinIp = $apiData['ip_opt'];
        $this->_optinDate = $apiData['timestamp_opt'];
        $this->_creationDate = $apiData['timestamp'];
        $this->_updateDate = $apiData['info_changed'];
        $this->_memberRating = $apiData['member_rating'];
        $this->_language = $apiData['language'];
        $this->_status = $apiData['status'];
        $this->_isGoldenMonkey = $apiData['is_gmonkey'];
        $this->_merges = $apiData->merges->toArray();
        $this->_lists = $apiData->lists->toArray();
        $this->_geo = $apiData->geo->toArray();
        $this->_clients = $apiData->clients->toArray();
        $this->_staticSegments = $apiData->static_segments->toArray();
        $this->_notes = $apiData->notes->toArray();

        if(isset($this->_merges['GROUPINGS'])) {
            foreach($this->_merges['GROUPINGS'] as $set) {
                $this->_groupSets[$set['id']] = array_map(
                    function($groupName) {
                        return trim(str_replace('\\,', ',', $groupName));
                    }, 
                    preg_split('/(?<!\\\\)\,\W*/i', $set['groups'])
                );

                if(count($this->_groupSets[$set['id']]) == 1 && empty($this->_groupSets[$set['id']][0])) {
                    $this->_groupSets[$set['id']] = [];
                }
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
        return new flow\mail\Address($this->_email);
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
        if($this->_signupIp) {
            return new link\Ip($this->_signupIp);
        }
    }

    public function getSignupDate() {
        if($this->_signupDate) {
            return new core\time\Date($this->_signupDate);
        }
    }

    public function getOptinIp() {
        if($this->_optinIp) {
            return new link\Ip($this->_optinIp);
        }
    }

    public function getOptinDate() {
        if($this->_optinDate) {
            return new core\time\Date($this->_optinDate);
        }
    }

    public function getCreationDate() {
        return new core\time\Date($this->_creationDate);
    }

    public function getUpdateDate() {
        if($this->_updateDate) {
            return new core\time\Date($this->_updateDate);
        }
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



// Entry
    public function setEmailAddress($address) {
        $address = flow\mail\Address::factory($address);

        if(!$address->isValid()) {
            throw new InvalidArgumentException(
                'Invalid email address: '.$address
            );
        }

        $this->_email = $address->getAddress();
        $this->_merges['EMAIL'] = $address->getAddress();

        return $this;
    }

    public function updateEmailAddress($address) {
        $this->setEmailAddress($address);
        $this->_mediator->updateEmailAddress($this->_listId, $this->_id, $this->_email);

        return $this;
    }

    public function setName($firstName, $surname) {
        $this->_merges['FNAME'] = $firstName;
        $this->_merges['LNAME'] = $surname;
        
        return $this;
    }

    public function updateName($firstName, $surname) {
        $this->setName($firstName, $surname);
        
        $this->_mediator->updateMemberName(
            $this->_listId, 
            $this->_id, 
            $this->_merges['FNAME'],
            $this->_merges['LNAME']
        );

        return $this;
    }

    public function setEmailType($type) {
        $type = strtolower($type);

        switch($type) {
            case 'html':
            case 'text':
                break;

            default:
                $type = 'html';
        }

        $this->_emailType = $type;
        return $this;
    }

    public function updateEmailType($type) {
        $this->setEmailType($type);
        $this->_mediator->updateMemberEmailType($this->_listId, $this->_id, $this->_emailType);
        return $this;
    }

    public function setMergeData(array $data) {
        foreach($data as $key => $value) {
            $this->_merges[strtoupper($key)] = $value;
        }

        return $this;
    }

    public function updateMergeData(array $data) {
        $this->setMergeData($data);
        $this->_mediator->updateMember($this->_listId, $this->_id, $this->_merges);
        return $this;
    }


    public function setGroups(array $groups) {
        $this->_groupSets = [];
        return $this->addGroups($groups);
    }

    public function addGroups(array $groups) {
        foreach($groups as $group) {
            $this->_groupSets[$group->getGroupSet()->getId()][] = $group->getName();
        }

        foreach($this->_groupSets as $i => $set) {
            $this->_groupSets[$i] = array_unique($set);
        }

        return $this;
    }

    public function setGroupSetData(array $data) {
        $this->_groupSets = $data;
        return $this;
    }


    public function save() {
        $merges = $this->_merges;
        $merges['GROUPINGS'] = [];

        foreach($this->_groupSets as $id => $groups) {
            array_walk($groups, function($group) {
                return str_replace(',', '\\,', $group);
            });

            $merges['GROUPINGS'][] = [
                'id' => $id,
                'groups' => implode(',', $groups)
            ];
        }

        if(empty($merges['GROUPINGS'])) {
            foreach($this->_mediator->fetchGroupSets($this->_listId) as $set) {
                $merges['GROUPINGS'][] = [
                    'id' => $set->getId(),
                    'groups' => ''
                ];
            }
        }

        $this->_mediator->updateMember($this->_listId, $this->_id, $merges, $this->_emailType, true);
        return $this;
    }
    
    public function unsubscribe($sendGoodbye=false, $sendNotify=false) {
        $this->_mediator->unsubscribe($this->_listId, $this->_email, (bool)$sendGoodbye, (bool)$sendNotify);
        return $this;
    }

    public function delete($sendGoodbye=false, $sendNotify=false) {
        $this->_mediator->deleteMember($this->_listId, $this->_email, (bool)$sendGoodbye, (bool)$sendNotify);
        return $this;
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
            'signupDate' => $this->getSignupDate(),
            'optinIp' => $this->getOptinIp(),
            'optinDate' => $this->getOptinDate(),
            'creationDate' => $this->getCreationDate(),
            'updateDate' => $this->getUpdateDate(),
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