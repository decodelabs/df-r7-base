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
use df\flex;
    
class Mediator implements IMediator, \Serializable {

    use spur\THttpMediator;

    const API_URL = 'http://api.mailchimp.com/1.3/';

    protected $_isSecure = false;
    protected $_apiKey;
    protected $_dataCenter = 'us1';
    protected $_activeUrl;

    public function __construct($apiKey, $secure=false) {
        $this->setApiKey($apiKey);
        $this->isSecure($secure);
    }

// Serialize
    public function serialize() {
        return serialize([
            'key' => $this->_apiKey,
            'secure' => $this->_isSecure
        ]);
    }

    public function unserialize($data) {
        $data = unserialize($data);
        $this->__construct($data['key'], $data['secure']);
        return $this;
    }

// Client
    public function isSecure($flag=null) {
        if($flag !== null) {
            $this->_isSecure = (bool)$flag;
            return $this;
        }

        return $this->_isSecure;
    }


// Api key
    public function setApiKey($key) {
        $this->_apiKey = $key;

        if(strstr($key, '-')) {
            $parts = explode('-', $key, 2);
            $key = array_shift($parts);

            if($dataCenter = array_shift($parts)) {
                $this->_dataCenter = $dataCenter;
            }
        }

        return $this;
    }

    public function getApiKey() {
        return $this->_apiKey;
    }

    public function getDataCenterId() {
        return $this->_dataCenter;
    }


## Entry points

// Lists
    public function fetchAllLists() {
        $json = $this->requestJson('post', 'lists');
        $output = [];

        foreach($json->data as $listData) {
            $list = new SubscriberList($this, $listData);
            $output[$list->getId()] = $list;
        }

        return $output;
    }

    public function fetchList($id) {
        $json = $this->requestJson('post', 'lists', [
            'filter' => ['list_id' => $id]
        ]);

        return new SubscriberList($this, $json->data->{0});
    }

    public function ensureSubscription($listId, $emailAddress, array $merges=[], array $groups=[], $emailType='html', $sendWelcome=false) {
        $groupings = [];

        foreach($groups as $group) {
            if($group instanceof IGroup) {
                $groupings[$group->getGroupSet()->getId()][] = $group->getPreparedName();
                continue;
            }

            if($group instanceof IGroupSet) {
                $group = $group->getId();
            }

            if(!is_scalar($group)) {
                continue;
            }

            if(!isset($groupings[$group])) {
                $groupings[$group] = [];
            }
        }

        $merges['EMAIL'] = $emailAddress;
        $merges['GROUPINGS'] = [];

        foreach($groupings as $id => $set) {
            $merges['GROUPINGS'][] = [
                'id' => $id,
                'groups' => implode(',', $set)
            ];
        }

        /*
        if(empty($merges['GROUPINGS'])) {
            foreach($this->fetchGroupSets($listId) as $set) {
                $merges['GROUPINGS'][] = [
                    'id' => $set->getId(),
                    'groups' => ''
                ];
            }
        }
        */

        $emailType = strtolower($emailType);

        if(!in_array($emailType, ['html', 'text'])) {
            $emailType = 'html';
        }

        $this->requestRaw('post', 'listSubscribe', [
            'id' => $listId,
            'email_address' => $emailAddress,
            'merge_vars' => $merges,
            'email_type' => $emailType,
            'double_optin' => false,
            'update_existing' => true,
            'replace_interests' => true,
            'send_welcome' => $sendWelcome
        ]);

        return $this;
    }

    public function unsubscribe($listId, $emailAddress, $sendGoodbye=false, $sendNotify=false) {
        $emailAddress = flow\mail\Address::factory($emailAddress);

        $this->requestRaw('post', 'listUnsubscribe', [
            'id' => $listId, 
            'email_address' => $emailAddress,
            'delete_member' => true,
            'send_goodbye' => (bool)$sendGoodbye,
            'send_notify' => (bool)$sendNotify
        ]);

        return $this;
    }



// Group sets
    public function fetchGroupSets($listId) {
        $output = [];

        try {
            $json = $this->requestJson('post', 'listInterestGroupings', [
                'id' => $listId
            ]);
        } catch(spur\ApiDataError $e) {
            return $output;
        }

        foreach($json as $setData) {
            $set = new GroupSet($this, $listId, $setData);
            $output[$set->getId()] = $set;
        }

        return $output;
    }

    public function addGroupSet($listId, $name, array $groupNames, $type=null) {
        if(!in_array($type, ['checkboxes', 'hidden', 'dropdown', 'radio'])) {
            $type = 'checkboxes';
        }

        $setId = $this->requestJson('post', 'listInterestGroupingAdd', [
            'id' => $listId, 
            'name' => $name,
            'type' => $type,
            'groups' => $groupNames
        ])->getValue();

        $groups = [];
        $bit = 0;

        foreach($groupNames as $groupName) {
            $groups[] = [
                'bit' => ++$bit,
                'name' => $groupName,
                'display_order' => $bit,
                'subscribers' => 0
            ];
        }

        return new GroupSet($this, $listId, new core\collection\Tree([
            'id' => $setId,
            'name' => $name,
            'form_field' => $type,
            'display_order' => 1,
            'groups' => $groups
        ]));
    }

    public function renameGroupSet($setId, $newName) {
        $this->requestRaw('post', 'listInterestGroupingUpdate', [
            'grouping_id' => $setId, 
            'name' => 'name', 
            'value' => $newName
        ]);

        return $this;
    }

    public function deleteGroupSet($setId) {
        try {
            $this->requestRaw('post', 'listInterestGroupingDel', [
                'grouping_id' => $setId
            ]);
        } catch(spur\ApiDataError $e) {}

        return $this;
    }


// Groups
    public function fetchGroups($listId) {
        $sets = $this->fetchGroupSets($listId);
        $output = [];

        foreach($sets as $set) {
            foreach($set->getGroups() as $group) {
                $output[] = $group;
            }
        }

        return $output;
    }

    public function addGroup($listId, $groupSetId, $name) {
        $this->requestRaw('post', 'listInterestGroupAdd', [
            'id' => $listId, 
            'grouping_id' => $groupSetId,
            'group_name' => $name
        ]);

        return $this;
    }

    public function renameGroup($listId, $groupSetId, $groupId, $newName) {
        $this->requestRaw('post', 'listInterestGroupUpdate', [
            'id' => $listId, 
            'old_name' => $groupId, 
            'new_name' => $newName, 
            'grouping_id' => $groupSetId
        ]);

        return $this;
    }

    public function deleteGroup($listId, $groupSetId, $groupId) {
        $this->requestRaw('post', 'listInterestGroupDel', [
            'id' => $listId, 
            'group_name' => $groupId, 
            'grouping_id' => $groupSetId
        ]);

        return $this;
    }


// Members
    public function fetchMember($listId, $emailAddress) {
        $json = $this->requestJson('post', 'listMemberInfo', [
            'id' => $listId, 
            'email_address' => $emailAddress
        ]);

        if(!isset($json->data->{0}) || isset($json->data->{0}->error)) {
            throw new RuntimeException(
                'Member '.$emailAddress.' could not be found'
            );
        }

        return new Member($this, $listId, $json->data->{0});
    }

    public function fetchMemberSet($listId, array $emailAddresses) {
        $json = $this->requestJson('post', 'listMemberInfo', [
            'id' => $listId, 
            'email_address' => $emailAddresses
        ]);

        $output = [];

        foreach($json->data as $memberData) {
            if(isset($memberData->error)) {
                continue;
            }

            $member = new Member($this, $listId, $memberData);
            $output[$member->getId()] = $member;
        }
        
        return $output;
    }

    public function updateEmailAddress($listId, $memberId, $newEmailAddress) {
        $newEmailAddress = flow\mail\Address::factory($newEmailAddress);

        $this->requestRaw('post', 'listUpdateMember', [
            'id' => $listId,
            'email_address' => $memberId, 
            'merge_vars' => [
                'EMAIL' => $newEmailAddress->getAddress()
            ]
        ]);

        return $this;
    }

    public function updateMemberName($listId, $memberId, $firstName, $surname) {
        $this->requestRaw('post', 'listUpdateMember', [
            'id' => $listId,
            'email_address' => $memberId, 
            'merge_vars' => [
                'FNAME' => $firstName,
                'LNAME' => $surname
            ]
        ]);

        return $this;
    }

    public function updateMemberEmailType($listId, $memberId, $type) {
        switch($type) {
            case 'html':
            case 'text':
                break;

            default:
                $type = 'html';
        }

        $this->requestRaw('post', 'listUpdateMember', [
            'id' => $listId,
            'email_address' => $memberId, 
            'merge_vars' => [],
            'email_type' => $type
        ]);

        return $this;
    }

    public function updateMember($listId, $memberId, array $mergeData, $emailType=null, $replaceInterests=false) {
        switch($emailType) {
            case null:
            case 'html':
            case 'text':
                break;

            default:
                $type = 'html';
        }

        $args = [
            'id' => $listId,
            'email_address' => $memberId, 
            'merge_vars' => $mergeData,
        ];

        if($emailType !== null) {
            $args['email_type'] = $emailType;
        }

        if($replaceInterests) {
            $args['replace_interests'] = true;
        }

        $this->requestRaw('post', 'listUpdateMember', $args);

        return $this;
    }

    public function deleteMember($listId, $memberId, $sendGoodbye=false, $sendNotify=false) {
        $this->requestRaw('post', 'listUnsubscribe', [
            'id' => $listId, 
            'email_address' => $memberId, 
            'delete_member' => true, 
            'send_goodbye' => (bool)$sendGoodbye,
            'send_notify' => (bool)$sendNotify
        ]);

        return $this;
    }


// Hooks
    public function fetchWebHooks($listId) {
        $json = $this->requestJson('post', 'listWebhooks', [
            'id' => $listId]
        );

        $output = [];

        foreach($json as $hookData) {
            $output[] = new WebHook($this, $listId, $hookData);
        }

        return $output;
    }

    public function addWebHook($listId, $url, array $actions, array $sources) {
        $actions = WebHook::normalizeActions($actions);
        $sources = WebHook::normalizeSources($sources);

        $this->requestRaw('post', 'listWebhookAdd', [
            'id' => $listId, 
            'url' => $url, 
            'actions' => $actions, 
            'sources' => $sources
        ]);

        return new WebHook($this, $listId, new core\collection\Tree([
            'url' => $url,
            'actions' => $actions,
            'sources' => $sources
        ]));
    }

    public function deleteWebHook($listId, $url) {
        $this->requestRaw('post', 'listWebhookDel', [
            'id' => $listId, 
            'url' => $url]
        );

        return $this;
    }



// IO
    public function createRequest($method, $path, array $args=[], array $headers=[]) {
        $url = $this->createUrl($path);
        $request = link\http\request\Base::factory($url);
        $request->setMethod($method);

        $args['apikey'] = $this->_apiKey;
        $request->setBodyData(flex\json\Codec::encode($args));

        if(!empty($headers)) {
            $request->getHeaders()
                ->set('content-type', 'application/json')
                ->import($headers);
        }

        return $request;
    }

    public function createUrl($method) {
        if(!$this->_activeUrl) {
            $this->_activeUrl = link\http\Url::factory(self::API_URL);
            $this->_activeUrl->setDomain($this->_dataCenter.'.'.$this->_activeUrl->getDomain());
            $this->_activeUrl->query->output = 'json';
            $this->_activeUrl->isSecure($this->_isSecure);
        }

        $url = clone $this->_activeUrl;
        $url->query->method = $method;

        return $url;
    }

    protected function _isResponseOk(link\http\IResponse $response) {
        if(!$response->isOk()) {
            return false;
        }

        $data = flex\json\Codec::decode($response->getContent());
        $headers = $response->getHeaders();

        if(isset($data['error']) || $headers->has('X-Mailchimp-Api-Error-Code')) {
            return false;
        }

        return true;
    }

    protected function _extractResponseError(link\http\IResponse $response) {
        $data = flex\json\Codec::decode($response->getContent());
        $error = isset($data['error']) ? $data['error'] : 'Undefined chimp calamity!';
        //$code = $headers->get('X-Mailchimp-Api-Error-Code');

        return $error;
    }

/*
    protected static $_functionMap = [
        'campaignUnschedule' => ['cid'],
        'campaignSchedule' => ['cid', 'schedule_time', 'schedule_time_b'],
        'campaignScheduleBatch' => ['cid', 'schedule_time', 'num_batches', 'stagger_mins'],
        'campaignResume' => ['cid'],
        'campaignPause' => ['cid'],
        'campaignSendNow' => ['cid'],
        'campaignSendTest' => ['cid', 'test_emails', 'send_type'],
        'campaignSegmentTest' => ['list_id', 'options'],
        'campaignCreate' => ['type', 'options', 'content', 'segment_opts', 'type_opts'],
        'campaignUpdate' => ['cid', 'name', 'value'],
        'campaignReplicate' => ['cid'],
        'campaignDelete' => ['cid'],
        'campaigns' => ['filters', 'start', 'limit', 'sort_field', 'sort_dir'],
        'campaignStats' => ['cid'],
        'campaignClickStats' => ['cid'],
        'campaignEmailDomainPerformance' => ['cid'],
        'campaignMembers' => ['cid', 'status', 'start', 'limit'],
        'campaignHardBounces' => ['cid', 'start', 'limit'],
        'campaignSoftBounces' => ['cid', 'start', 'limit'],
        'campaignUnsubscribes' => ['cid', 'start', 'limit'],
        'campaignAbuseReports' => ['cid', 'since', 'start', 'limit'],
        'campaignAdvice' => ['cid'],
        'campaignAnalytics' => ['cid'],
        'campaignGeoOpens' => ['cid'],
        'campaignGeoOpensForCountry' => ['cid', 'code'],
        'campaignEepUrlStats' => ['cid'],
        'campaignBounceMessage' => ['cid', 'email'],
        'campaignBounceMessages' => ['cid', 'start', 'limit', 'since'],
        'campaignEcommOrders' => ['cid', 'start', 'limit', 'since'],
        'campaignShareReport' => ['cid', 'opts'],
        'campaignContent' => ['cid', 'for_archive'],
        'campaignTemplateContent' => ['cid'],
        'campaignOpenedAIM' => ['cid', 'start', 'limit'],
        'campaignNotOpenedAIM' => ['cid', 'start', 'limit'],
        'campaignClickDetailAIM' => ['cid', 'url', 'start', 'limit'],
        'campaignEmailStatsAIM' => ['cid', 'email_address'],
        'campaignEmailStatsAIMAll' => ['cid', 'start', 'limit'],
        'campaignEcommOrderAdd' => ['order'],
        'lists' => ['filters', 'start', 'limit', 'sort_field', 'sort_dir'],
        'listMergeVars' => ['id'],
        'listMergeVarAdd' => ['id', 'tag', 'name', 'options'],
        'listMergeVarUpdate' => ['id', 'tag', 'options'],
        'listMergeVarDel' => ['id', 'tag'],
        'listMergeVarReset' => ['id', 'tag'],
        'listInterestGroupings' => ['id'],
        'listInterestGroupAdd' => ['id', 'group_name', 'grouping_id'],
        'listInterestGroupDel' => ['id', 'group_name', 'grouping_id'],
        'listInterestGroupUpdate' => ['id', 'old_name', 'new_name', 'grouping_id'],
        'listInterestGroupingAdd' => ['id', 'name', 'type', 'groups'],
        'listInterestGroupingUpdate' => ['grouping_id', 'name', 'value'],
        'listInterestGroupingDel' => ['grouping_id'],
        'listWebhooks' => ['id'],
        'listWebhookAdd' => ['id', 'url', 'actions', 'sources'],
        'listWebhookDel' => ['id', 'url'],
        'listStaticSegments' => ['id'],
        'listStaticSegmentAdd' => ['id', 'name'],
        'listStaticSegmentReset' => ['id', 'seg_id'],
        'listStaticSegmentDel' => ['id', 'seg_id'],
        'listStaticSegmentMembersAdd' => ['id', 'seg_id', 'batch'],
        'listStaticSegmentMembersDel' => ['id', 'seg_id', 'batch'],
        'listSubscribe' => ['id', 'email_address', 'merge_vars', 'email_type', 'double_optin', 'update_existing', 'replace_interests', 'send_welcome'],
        'listUnsubscribe' => ['id', 'email_address', 'delete_member', 'send_goodbye', 'send_notify'],
        'listUpdateMember' => ['id', 'email_address', 'merge_vars', 'email_type', 'replace_interests'],
        'listBatchSubscribe' => ['id', 'batch', 'double_optin', 'update_existing', 'replace_interests'],
        'listBatchUnsubscribe' => ['id', 'emails', 'delete_member', 'send_goodbye', 'send_notify'],
        'listMembers' => ['id', 'status', 'since', 'start', 'limit', 'sort_dir'],
        'listMemberInfo' => ['id', 'email_address'],
        'listMemberActivity' => ['id', 'email_address'],
        'listAbuseReports' => ['id', 'start', 'limit', 'since'],
        'listGrowthHistory' => ['id'],
        'listActivity' => ['id'],
        'listLocations' => ['id'],
        'listClients' => ['id'],
        'templates' => ['types', 'category', 'inactives'],
        'templateInfo' => ['tid', 'type'],
        'templateAdd' => ['name', 'html'],
        'templateUpdate' => ['id', 'values'],
        'templateDel' => ['id'],
        'templateUndel' => ['id'],
        'getAccountDetails' => ['exclude'],
        'getVerifiedDomains' => [],
        'generateText' => ['type', 'content'],
        'inlineCss' => ['html', 'strip_css'],
        'folders' => ['type'],
        'folderAdd' => ['name', 'type'],
        'folderUpdate' => ['fid', 'name', 'type'],
        'folderDel' => ['fid', 'type'],
        'ecommOrders' => ['start', 'limit', 'since'],
        'ecommOrderAdd' => ['order'],
        'ecommOrderDel' => ['store_id', 'order_id'],
        'listsForEmail' => ['email_address'],
        'campaignsForEmail' => ['email_address', 'options'],
        'chimpChatter' => [],
        'searchMembers' => ['query', 'id', 'offset'],
        'searchCampaigns' => ['query', 'offset', 'snip_start', 'snip_end'],
        'apikeys' => ['username', 'password', 'expired'],
        'apikeyAdd' => ['username', 'password'],
        'apikeyExpire' => ['username', 'password'],
        'ping' => [],
        'deviceRegister' => ['mobile_key', 'details'],
        'deviceUnregister' => ['mobile_key', 'device_id'],
        'gmonkeyAdd' => ['id', 'email_address'],
        'gmonkeyDel' => ['id', 'email_address'],
        'gmonkeyMembers' => [],
        'gmonkeyActivity' => []
    ];
    */
}