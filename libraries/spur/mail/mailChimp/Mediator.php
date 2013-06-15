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
    
class Mediator implements IMediator {

    const API_URL = 'http://api.mailchimp.com/1.3/';

    protected $_httpClient;
    protected $_isSecure = false;
    protected $_apiKey;
    protected $_dataCenter = 'us1';
    protected $_activeUrl;

    public function __construct($apiKey, $secure=false) {
        $this->_httpClient = new halo\protocol\http\Client();
        $this->setApiKey($apiKey);
        $this->isSecure($secure);
    }

    public function getHttpClient() {
        return $this->_httpClient;
    }

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


// IO
    public function __call($method, array $args) {
        return $this->callServer($method, $args);
    }

    public function callServer($method, array $args=array()) {
        if(!$this->_activeUrl) {
            $this->_activeUrl = halo\protocol\http\Url::factory(self::API_URL);
            $this->_activeUrl->setDomain($this->_dataCenter.'.'.$this->_activeUrl->getDomain());
            $this->_activeUrl->query->output = 'php';
            $this->_activeUrl->isSecure($this->_isSecure);
        }

        if(!isset(self::$_functionMap[$method])) {
            throw new BadMethodCallException(
                'Method '.$method.' is not recognised'
            );
        }

        $newArgs = [
            'apikey' => $this->_apiKey
        ];

        foreach(self::$_functionMap[$method] as $arg) {
            $newArgs[$arg] = array_shift($args);
        }

        $url = clone $this->_activeUrl;
        $url->query->method = $method;

        $request = halo\protocol\http\request\Base::factory($url);
        $request->setMethod('post');
        $request->setPostData($newArgs);
        
        $response = $this->_httpClient->sendRequest($request);
        $data = unserialize($response->getContent());
        $headers = $response->getHeaders();

        if(isset($data['error']) || $headers->has('X-Mailchimp-Api-Error-Code')) {
            $error = isset($data['error']) ? $data['error'] : 'Undefined chimp calamity!';
            $code = $headers->get('X-Mailchimp-Api-Error-Code');

            throw new RuntimeException(
                $error, $code
            );  
        }

        return $data;
    }

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
}