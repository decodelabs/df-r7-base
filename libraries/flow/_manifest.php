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
    public function sendLegacyMail(flow\mail\ILegacyMessage $message, flow\mail\ITransport $transport=null);
    public function forceSendLegacyMail(flow\mail\ILegacyMessage $message, flow\mail\ITransport $transport=null);
    public function getDefaultMailTransportName($forceSend=false);
    public function getMailModel();

// Notification
    public function newNotification($subject, $body, $to=null, $from=null, $forceSend=false);
    public function sendNotification(INotification $notification, $forceSend=false);


// Lists
    public function getListSources();
    public function getListSource($id);
    public function hasListSource($id);
    public function getListManifest();
    public function getAvailableListAdapters();
    public function getListAdapterSettingsFields($adapter);
    public function getListOptions();
    public function getListGroupOptions();

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


interface INotification extends flow\mail\IJournalableMessage {

    const TEXT = 'text';
    const SIMPLE_TAGS = 'simpleTags';
    const HTML = 'html';

    public function setSubject($subject);
    public function getSubject();
    public function setBody($body);
    public function getBody();
    public function setBodyType($type);
    public function getBodyType();
    public function getBodyHtml();

    public function shouldSendToAdmin(bool $flag=null);
    public function setTo($to);
    public function addTo($to);
    public function clearTo();
    public function hasRecipients();
    public function shouldFilterClient(bool $flag=null);
    public function shouldForceSend(bool $flag=null);

    public function addToEmail($email, $name=null);
    public function getToEmails();
    public function removeToEmail($email);
    public function clearToEmails();

    public function addToUser($id);
    public function getToUsers();
    public function getToUserIds();
    public function removeToUser($id);
    public function clearToUsers();

    public function setFromEmail($email=null, $name=null);
    public function getFromEmail();
}

interface INotificationProxy {
    public function toNotification($to=null, $from=null);
}