<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flow;

use df;
use df\core;
use df\flow;
use df\user;



// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}



// Interfaces
interface IManager extends core\IManager {

// Mail
    public function sendMail(flow\mail\IMessage $message, flow\mail\ITransport $transport=null);
    public function forceSendMail(flow\mail\IMessage $message, flow\mail\ITransport $transport=null);

    public function getDefaultMailTransportName($forceSend=false);
    public function getMailModel();


// Lists
    public function getListSources();
    public function getListSource($id);
    public function hasListSource($id);
    public function getListManifest();
    public function getAvailableListAdapters();
    public function getListAdapterSettingsFields($adapter);
    public function getListOptions();
    public function getListGroupOptions();

    public function getListExternalLinkFor($sourceId);

    public function getPrimaryGroupOptionsFor($sourceId, $nested=false);
    public function getPrimaryGroupIdListFor($sourceId);

    public function subscribeClientToPrimaryList($sourceId, array $groups=null, $replace=false);
    public function subscribeClientToList($sourceId, $listId, array $groups=null, $replace=false);
    public function subscribeClientToGroups(array $compoundGroupIds, $replace=false);
    public function subscribeUserToPrimaryList(user\IClientDataObject $client, $sourceId, array $groups=null, $replace=false);
    public function subscribeUserToList(user\IClientDataObject $client, $sourceId, $listId, array $groups=null, $replace=false);
    public function subscribeUserToGroups(user\IClientDataObject $client, array $compoundGroupIds, $replace=false);

    public function getClientSubscribedGroups();
    public function getClientSubscribedGroupsFor($sourceId);
    public function getClientSubscribedGroupsIn($sourceId, $listId);
    public function getClientSubscribedPrimaryGroupsFor($sourceId);
    public function isClientSubscribed($sourceId, $listId=null, $groupId=null);

    public function updateListUserDetails($oldEmail, user\IClientDataObject $client);

    public function unsubscribeClientFromPrimaryList($sourceId);
    public function unsubscribeClientFromList($sourceId, $listId);
    public function unsubscribeUserFromPrimaryList(user\IClientDataObject $client, $sourceId);
    public function unsubscribeUserFromList(user\IClientDataObject $client, $sourceId, $listId);

// Flash
    public function setFlashLimit($limit);
    public function getFlashLimit();

    public function newFlashMessage($id, $message=null, $type=null);
    public function processFlashQueue();

    public function flash($id, $message=null, $type=null);
    public function flashNow($id, $message=null, $type=null);
    public function flashAlways($id, $message=null, $type=null);

    public function addConstantFlash(IFlashMessage $message);
    public function getConstantFlash($id);
    public function getConstantFlashes();
    public function removeConstantFlash($id);
    public function clearConstantFlashes();

    public function queueFlash(IFlashMessage $message, $instantIfSpace=false);
    public function addInstantFlash(IFlashMessage $message);
    public function getInstantFlashes();
    public function removeQueuedFlash($id);
}


interface IFlashMessage {

    const INFO = 'info';
    const SUCCESS = 'success';
    const ERROR = 'error';
    const WARNING = 'warning';
    const DEBUG = 'debug';

    public function getId();
    public function setType($type);
    public function getType();
    public function isDebug();

    public function isDisplayed(bool $flag=null);
    public function setMessage($message);
    public function getMessage();
    public function setDescription($description);
    public function getDescription();

    public function setLink($link, $text=null);
    public function getLink();
    public function setLinkText($text);
    public function getLinkText();
    public function clearLink();
}



class FlashQueue implements \Serializable {

    public $limit = 15;
    public $constant = [];
    public $queued = [];
    public $instant = [];

    public function serialize() {
        $data = ['l' => $this->limit];

        if(!empty($this->constant)) {
            $data['c'] = $this->constant;
        }

        if(!empty($this->queued)) {
            $data['q'] = $this->queued;
        }

        if(!empty($this->instant)) {
            $data['i'] = $this->instant;
        }

        return serialize($data);
    }

    public function unserialize($data) {
        $data = unserialize($data);
        $this->limit = $data['l'];

        if(isset($data['c'])) {
            $this->constant = $data['c'];
        }

        if(isset($data['q'])) {
            $this->queued = $data['q'];
        }

        if(isset($data['i'])) {
            $this->instant = $data['i'];
        }
    }
}