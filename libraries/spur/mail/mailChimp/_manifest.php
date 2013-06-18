<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\mail\mailChimp;

use df;
use df\core;
use df\spur;

    
// Exceptions
interface IException {}
class BadMethodCallException extends \BadMethodCallException implements IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}


// Interfaces
interface IMediator {
    public function getHttpClient();
    public function isSecure($flag=null);

// Api key
    public function setApiKey($key);
    public function getApiKey();
    public function getDataCenterId();


// Entry points
    public function fetchAllLists();
    public function fetchList($id);
    public function ensureSubscription($listId, $emailAddress, array $merges, array $groups, $emailType='html', $sendWelcome=false);

    public function fetchGroupSets($listId);
    public function fetchGroups($listId);
    public function addGroupSet($listId, $name, array $groupNames, $type=null);
    public function renameGroupSet($setId, $newName);
    public function deleteGroupSet($setId);

    public function fetchMember($listId, $emailAddress);
    public function fetchMemberSet($listId, array $emailAddresses);
    public function updateEmailAddress($listId, $memberId, $newEmailAddress);

    public function fetchWebHooks($listId);
    public function addWebHook($listId, $url, array $actions, array $sources);
    public function deleteWebHook($listId, $url);

// IO
    public function __call($method, array $args);
    public function callServer($method);
    public function callServerArgs($method, array $args=array());
}


interface IApiRepresentation {
    //public function __construct(IMediator $mediator, array $apiData);
    public function getMediator();
}

interface IList extends IApiRepresentation {
    public function getId();
    public function getWebId();
    public function getName();
    public function getCreationDate();
    public function hasEmailTypeOption();
    public function shouldUseAwesomeBar();
    public function getDefaultFromAddress();
    public function getDefaultSubject();
    public function getDefaultLanguage();
    public function getListRating();
    public function getShortSubscribeUrl();
    public function getLongSubscribeUrl();
    public function getBeamerAddress();
    public function getVisibility();

    public function getStats();
    public function countMembers();
    public function countMembersSinceSend();
    public function countUnsubscribers();
    public function countUnsubscribersSinceSend();
    public function countCleaned();
    public function countCleanedSinceSend();
    public function countCampaigns();
    public function countGroupSets();
    public function countGroups();
    public function countMergeVars();
    public function getAverageSubscriptionRate();
    public function getAverageUnsubscriptionRate();
    public function getTargetSubscriptionRate();
    public function getOpenRate();
    public function getClickRate();

    public function getModules();

    public function ensureSubscription($emailAddress, array $merges, array $groups, $emailType='html', $sendWelcome=false);

    public function fetchGroupSets();
    public function fetchGroups();
    public function addGroupSet($name, array $groupNames, $type=null);

    public function fetchMember($emailAddress);
    public function fetchMemberSet(array $emailAddresses);

    public function fetchWebHooks();
    public function addWebHook($url, array $actions, array $sources);
    public function deleteWebHook($url);
}


interface IGroupSet extends IApiRepresentation {
    public function getListId();
    public function getId();
    public function getName();
    public function getFormFieldType();
    public function getDisplayOrder();

    public function getGroups();
    public function getGroup($bit);
    public function getGroupNameString();
    public function addGroup($name);
    public function _removeGroup($bit);

    public function rename($newName);
    public function delete();
}

interface IGroup extends IApiRepresentation {
    public function getGroupSet();
    public function getBit();
    public function getCompoundId();
    public function getName();
    public function getPreparedName();
    public function getDisplayOrder();
    public function countSubscribers();

    public function rename($newName);
    public function delete();
}


interface IMember extends IApiRepresentation {
    public function getListId();
    public function getEmailAddress();
    public function getId();
    public function getWebId();
    public function getEmailType();
    public function getSignupIp();
    public function getSignupDate();
    public function getOptinIp();
    public function getOptinDate();
    public function getCreationDate();
    public function getUpdateDate();
    public function getMemberRating();
    public function getLanguage();
    public function getStatus();
    public function isGoldenMonkey();
    public function getMergeData();
    public function getListData();
    public function getGeoData();
    public function getClientData();
    public function getStaticSegmentData();
    public function getNotes();
    public function getGroupSetData();

    public function setEmailAddress($address);
    public function updateEmailAddress($address);
    public function setName($firstName, $surname);
    public function updateName($firstName, $surname);
    public function setEmailType($type);
    public function updateEmailType($type);
    public function setMergeData(array $data);
    public function updateMergeData(array $data);

    public function setGroups(array $groups);
    public function addGroups(array $groups);
    
    public function save();
}

interface IWebHook extends IApiRepresentation {
    public static function getAvailableActions();
    public static function normalizeActions(array $actions);
    public static function getAvailableSources();
    public static function normalizeSources(array $sources);

    public function getListId();
    public function getUrl();
    public function getActions();
    public function getSources();

    public function delete();
}