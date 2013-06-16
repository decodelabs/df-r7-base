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

    public function fetchGroupSets($listId);
    public function fetchGroups($listId);
    public function addGroupSet($listId, $name, array $groupNames, $type=null);

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

    public function fetchGroupSets();
    public function fetchGroups();
    public function addGroupSet($name, array $groupNames, $type=null);
}


interface IGroupSet extends IApiRepresentation {
    public function getListId();
    public function getId();
    public function getName();
    public function getFormFieldType();
    public function getDisplayOrder();

    public function getGroups();
    public function addGroup($name);
    public function _removeGroup($bit);

    public function rename($newName);
    public function delete();
}

interface IGroup extends IApiRepresentation {
    public function getGroupSet();
    public function getBit();
    public function getName();
    public function getDisplayOrder();
    public function countSubscribers();

    public function rename($newName);
    public function delete();
}