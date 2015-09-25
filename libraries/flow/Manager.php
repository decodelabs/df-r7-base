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
use df\flex;
use df\axis;

class Manager implements IManager, core\IShutdownAware {

    use core\TManager;

    const REGISTRY_PREFIX = 'manager://flow';
    const SESSION_NAMESPACE = 'flow';
    const FLASH_SESSION_KEY = 'flashQueue';

    protected $_flashQueue;
    protected $_isFlashQueueProcessed = false;

    public function onApplicationShutdown() {
        $this->_saveFlashQueue();
    }


## Mail
    public function sendMail(flow\mail\IMessage $message, flow\mail\ITransport $transport=null) {
        return $this->_sendMail($message, $transport);
    }

    public function forceSendMail(flow\mail\IMessage $message, flow\mail\ITransport $transport=null) {
        return $this->_sendMail($message, $transport, true);
    }

    protected function _sendMail(flow\mail\IMessage $message, flow\mail\ITransport $transport=null, $forceSend=false) {
        $isDefault = false;
        $name = null;

        if($transport === null) {
            $name = $this->getDefaultMailTransportName($forceSend);
            $transport = flow\mail\transport\Base::factory($name);
            $isDefault = true;
        }

        try {
            $output = $transport->send($message);
        } catch(\Exception $e) {
            if($isDefault
            && $name != 'Mail' 
            && $name != 'Capture') {
                $transport = flow\mail\transport\Base::factory('Mail');
                $output = $transport->send($message);
            } else {
                throw $e;
            }
        }

        if($message->shouldJournal()) {
            try {
                $model = $this->getMailModel();
                $model->journalMail($message);
            } catch(\Exception $e) {
                core\log\Manager::getInstance()->logException($e);
            }
        }

        return $output;
    }

    public function getDefaultMailTransportName($forceSend=false) {
        if(df\Launchpad::$application->isDevelopment() && !$forceSend) {
            return 'Capture';
        }

        $config = flow\mail\Config::getInstance();

        if(df\Launchpad::$application->isTesting() && $config->shouldCaptureInTesting() && !$forceSend) {
            return 'Capture';
        }

        $name = $config->getDefaultTransport();

        if(!flow\mail\transport\Base::getTransportClass($name)) {
            $name = 'Mail';
        }

        return $name;
    }

    public function getMailModel() {
        $model = axis\Model::factory('mail');

        if(!$model instanceof flow\mail\IMailModel) {
            throw new flow\mail\RuntimeException(
                'Mail model does not implements flow\\mail\\IMailModel'
            );
        }

        return $model;
    }


## Notification
    public function newNotification($subject, $body, $to=null, $from=null, $forceSend=false) {
        return new Notification($subject, $body, $to, $from, $forceSend);
    }

    public function sendNotification(INotification $notification, $forceSend=false) {
        $emails = $notification->getToEmails();
        $userManager = user\Manager::getInstance();
        $userModel = $userManager->getUserModel();
        $userList = $notification->getToUsers();
        $keys = [];
        $isJustToAdmins = false;

        if($notification->shouldSendToAdmin()) {
            $config = flow\mail\Config::getInstance();

            foreach($config->getAdminAddresses() as $address) {
                if($address->isValid()) {
                    $emails[$address->getAddress()] = $address->getName();
                }
            }

            $isJustToAdmins = true;
        }

        foreach($userList as $key => $user) {
            $isJustToAdmins = false;

            if($user === null) {
                $keys[] = $key;
            } else {
                $emails[$user->getEmail()] = $user->getFullName();
            }
        }

        $clientList = $userModel->getClientDataList($keys, array_keys($emails));
        $client = $userManager->client;

        foreach($clientList as $user) {
            $emails[$user->getEmail()] = $user->getFullName();
        }

        if($notification->shouldFilterClient()) {
            unset($emails[$client->getEmail()]);
        } else if(!$notification->hasRecipients() 
                && !$notification->shouldSendToAdmin() 
                && $userManager->isLoggedIn()) {
            $isJustToAdmins = false;
            $emails = [$client->getEmail() => $client->getFullName()];
        }

        if(empty($emails)) {
            return $this;
        }

        $mail = new flow\mail\Message();
        $mail->setSubject($notification->getSubject());

        if($notification->getBodyType() == INotification::TEXT) {
            $mail->setBodyText((string)$notification->getBody());
        } else {
            $mail->setBodyHtml($notification->getBodyHtml());
        }
        
        $mail->isPrivate($notification->isPrivate());

        if($notification->shouldJournal()) {
            $mail->shouldJournal(true);
            $mail->setJournalName($notification->getJournalName());
            $mail->setJournalDuration($notification->getJournalDuration());
            $mail->setJournalObjectId1($notification->getJournalObjectId1());
            $mail->setJournalObjectId2($notification->getJournalObjectId2());
        }

        if($from = $notification->getFromEmail()) {
            $mail->setFromAddress($from);
        }

        if(!$forceSend && $notification->shouldForceSend()) {
            $forceSend = true;
        }

        if($isJustToAdmins) {
            foreach($emails as $address => $name) {
                $mail->addToAddress($address, $name);
            }

            if($forceSend) {
                $this->forceSendMail($mail);
            } else {
                $this->sendMail($mail);
            }
        } else {
            foreach($emails as $address => $name) {
                $activeMail = clone $mail;
                $activeMail->addToAddress($address, $name);
                if($forceSend) {
                    $this->forceSendMail($activeMail);
                } else {
                    $this->sendMail($activeMail);
                }
            }
        }

        return $this;
    }



## LISTS
    public function getListSources() {
        $config = flow\mail\Config::getInstance();
        $output = [];

        foreach($config->getListSources() as $id => $options) {
            try {
                $source = new flow\mailingList\Source($id, $options);
            } catch(flow\mailingList\IException $e) {
                core\log\Manager::getInstance()->logException($e);
                continue;
            }

            $output[$source->getId()] = $source;
        }
        
        return $output;
    }

    public function getListSource($id) {
        if($id instanceof flow\mailingList\ISource) {
            return $id;
        }

        $config = flow\mail\Config::getInstance();
        $options = $config->getListSource($id);

        try {
            return new flow\mailingList\Source($id, $options);
        } catch(flow\mailingList\IException $e) {
            return null;
        }
    }

    public function hasListSource($id) {
        $config = flow\mail\Config::getInstance();
        $options = $config->getListSource($id);

        return isset($option->adapter);
    }

    public function getListManifest() {
        $output = [];

        foreach($this->getListSources() as $sourceId => $source) {
            foreach($source->getManifest() as $listId => $list) {
                $output[$sourceId.'/'.$listId] = array_merge([
                    'id' => $listId,
                    'source' => $sourceId
                ], $list);
            }
        }

        return $output;
    }

    public function getAvailableListAdapters() {
        $output = [];

        foreach(df\Launchpad::$loader->lookupClassList('flow/mailingList/adapter') as $name => $class) {
            $output[$name] = $name;
        }

        ksort($output);
        return $output;
    }

    public function getListAdapterSettingsFields($adapter) {
        return flow\mailingList\adapter\Base::getSettingsFieldsFor($adapter);
    }

    public function getListOptions() {
        $output = [];

        foreach($this->getListSources() as $sourceId => $source) {
            foreach($source->getListOptions() as $listId => $name) {
                $output[$sourceId.'/'.$listId] = $name;
            }
        }

        return $output;
    }

    public function getListGroupOptions() {
        $output = [];

        foreach($this->getListSources() as $sourceId => $source) {
            foreach($source->getGroupOptions() as $groupId => $name) {
                $output[$sourceId.'/'.$groupId] = $name;
            }
        }

        return $output;
    }

    public function getPrimaryGroupOptionsFor($sourceId, $nested=false, $showSets=true) {
        if(!$source = $this->getListSource($sourceId)) {
            return [];
        }

        if(!$listId = $source->getPrimaryListId()) {
            return [];
        }

        return $source->getGroupOptionsFor($listId, $nested, $showSets);
    }

    public function getPrimaryGroupIdListFor($sourceId) {
        if(!$source = $this->getListSource($sourceId)) {
            return [];
        }

        if(!$listId = $source->getPrimaryListId()) {
            return [];
        }

        return $source->getGroupIdListFor($listId);
    }



    public function subscribeClientToPrimaryList($sourceId, array $groups=null, $replace=false) {
        $client = user\Manager::getInstance()->getClient();
        return $this->subscribeUserToPrimaryList($client, $sourceId, $groups, $replace);
    }

    public function subscribeClientToList($sourceId, $listId, array $groups=null, $replace=false)  {
        $client = user\Manager::getInstance()->getClient();
        return $this->subscribeUserToList($client, $sourceId, $listId, $groups, $replace);
    }

    public function subscribeClientToGroups(array $compoundGroupIds, $replace=false)  {
        $client = user\Manager::getInstance()->getClient();
        return $this->subscribeUserToGroups($client, $compoundGroupIds, $replace);
    }

    public function subscribeUserToPrimaryList(user\IClientDataObject $client, $sourceId, array $groups=null, $replace=false)  {
        if(!$source = $this->getListSource($sourceId)) {
            return $this;
        }

        if(!$listId = $source->getPrimaryListId()) {
            throw new flow\mailingList\RuntimeException(
                'No primary list has been set for mailing list source '.$source->getId()
            );
        }

        return $this->subscribeUserToList($client, $source, $listId, $groups, $replace);
    }

    public function subscribeUserToList(user\IClientDataObject $client, $sourceId, $listId, array $groups=null, $replace=false)  {
        if($source = $this->getListSource($sourceId)) {
            $source->subscribeUserToList($client, $listId, $groups, $replace);
        }

        return $this;
    }

    public function subscribeUserToGroups(user\IClientDataObject $client, array $compoundGroupIds, $replace=false)  {
        $manifest = [];

        foreach($compoundGroupIds as $id) {
            list($sourceId, $listId, $groupId) = explode('/', $id);
            $manifest[$sourceId][$listId][] = $groupId;
        }

        foreach($manifest as $sourceId => $lists) {
            foreach($lists as $listId => $groups) {
                $this->subscribeClientToList($client, $sourceId, $listId, $groups, $replace);
            }
        }

        return $this;
    }


    public function getClientSubscribedGroups() {
        $output = [];

        foreach($this->getListSources() as $sourceId => $source) {
            foreach($source->getClientManifest() as $listId => $groups) {
                $output[$sourceId.'/'.$listId] = $groups;
            }
        }

        return $output;
    }

    public function getClientSubscribedGroupsFor($sourceId) {
        if(!$source = $this->getListSource($sourceId)) {
            return [];
        }

        return $source->getClientManifest();
    }

    public function getClientSubscribedGroupsIn($sourceId, $listId) {
        if(!$source = $this->getListSource($sourceId)) {
            return [];
        }

        return $source->getClientSubscribedGroupsIn($listId);
    }

    public function getClientSubscribedPrimaryGroupsFor($sourceId) {
        if(!$source = $this->getListSource($sourceId)) {
            return [];
        }

        if(!$listId = $source->getPrimaryListId()) {
            return [];
        }

        return $source->getClientSubscribedGroupsIn($listId);
    }

    public function isClientSubscribed($sourceId, $listId=null, $groupId=null) {
        if(!$source = $this->getListSource($sourceId)) {
            return false;
        }

        if($listId === null) {
            $listId = $source->getPrimaryListId();
        }

        if(!$listId) {
            return false;
        }

        $manifest = $source->getClientManifest();

        if(!isset($manifest[$listId])) {
            return false;
        }

        if($groupId === null) {
            return true;        
        }
        
        return isset($manifest[$listId][$groupId]);
    }


    public function updateListUserDetails($oldEmail, user\IClientDataObject $client) {
        foreach($this->getListSources() as $source) {
            $source->updateListUserDetails($oldEmail, $client);
        }

        return $this;
    }



    public function unsubscribeClientFromPrimaryList($sourceId) {
        $client = user\Manager::getInstance()->getClient();
        return $this->unsubscribeUserFromPrimaryList($client, $sourceId);
    }

    public function unsubscribeClientFromList($sourceId, $listId) {
        $client = user\Manager::getInstance()->getClient();
        return $this->unsubscribeUserFromList($client, $sourceId, $listId);
    }

    public function unsubscribeUserFromPrimaryList(user\IClientDataObject $client, $sourceId) {
        if(!$source = $this->getListSource($sourceId)) {
            return $this;
        }

        if(!$listId = $source->getPrimaryListId()) {
            return $this;
        }

        return $this->unsubscribeUserFromList($client, $source, $listId);
    }

    public function unsubscribeUserFromList(user\IClientDataObject $client, $sourceId, $listId) {
        if(!$source = $this->getListSource($sourceId)) {
            return $this;
        }

        $source->unsubscribeUserFromList($client, $listId);
        return $this;
    }



## FLASH

// Limit
    public function setFlashLimit($limit) {
        $this->_loadFlashQueue();
        $this->_flashQueue->limit = (int)$limit;

        if($this->_flashQueue->limit <= 0) {
            $this->_flashQueue->limit = 1;
        }

        return $this;
    }

    public function getFlashLimit() {
        $this->_loadFlashQueue();
        return $this->_flashQueue->limit;
    }


    public function newFlashMessage($id, $message=null, $type=null) {
        return FlashMessage::factory($id, $message, $type);
    }


// Queue
    public function processFlashQueue() {
        if(!$this->_isFlashQueueProcessed) {
            $this->_loadFlashQueue();

            foreach($this->_flashQueue->instant as $id => $message) {
                if($message->isDisplayed()) {
                    if($message->canDisplayAgain()) {
                        $message->resetDisplayState();
                    } else {
                        unset($this->_flashQueue->instant[$id]);
                    }
                }
            }

            $limit = $this->_flashQueue->limit - count($this->_flashQueue->instant);

            for($i = 0; $i < $limit; $i++) {
                if(!$message = array_shift($this->_flashQueue->queued)) {
                    break;
                }

                $this->_flashQueue->instant[$message->getId()] = $message;
            }

            $this->_isFlashQueueProcessed = true;
        }

        return $this;
    }

    protected function _loadFlashQueue() {
        if($this->_flashQueue === null) {
            $session = user\Manager::getInstance()->getSessionNamespace(self::SESSION_NAMESPACE);
            $this->_flashQueue = $session->get(self::FLASH_SESSION_KEY);

            if(!$this->_flashQueue instanceof FlashQueue) {
                $this->_flashQueue = new FlashQueue();
            }
        }
    }

    protected function _saveFlashQueue() {
        if(!$this->_flashQueue instanceof FlashQueue) {
            return false;
        }

        $session = user\Manager::getInstance()->getSessionNamespace(self::SESSION_NAMESPACE);
        $session->set(self::FLASH_SESSION_KEY, $this->_flashQueue);
        
        return true;
    }


// Shortcuts
    public function flash($id, $message=null, $type=null) {
        $message = $this->newFlashMessage($id, $message, $type);
        $this->queueFlash($message);
        return $message;
    }

    public function flashNow($id, $message=null, $type=null) {
        $message = $this->newFlashMessage($id, $message, $type);
        $this->addInstantFlash($message);
        return $message;
    }

    public function flashAlways($id, $message=null, $type=null) {
        $message = $this->newFlashMessage($id, $message, $type);
        $this->addConstantFlash($message);
        return $message;
    }


// Constant
    public function addConstantFlash(IFlashMessage $message) {
        $this->_loadFlashQueue();
        $this->_flashQueue->constant[$message->getId()] = $message;
        return $this;
    }
    
    public function getConstantFlash($id) {
        $this->_loadFlashQueue();

        if(isset($this->_flashQueue->constant[$id])) {
            return $this->_flashQueue->constant[$id];
        }
        
        return null;
    }
    
    public function getConstantFlashes() {
        $this->_loadFlashQueue();
        return $this->_flashQueue->constant;
    }
    
    public function removeConstantFlash($id) {
        $this->_loadFlashQueue();
        unset($this->_flashQueue->constant[$id]);
        return $this;
    }
    
    public function clearConstantFlashes() {
        $this->_loadFlashQueue();
        $this->_flashQueue->constant = [];
        return $this;
    }
    
// Queued
    public function queueFlash(IFlashMessage $message, $instantIfSpace=false) {
        $this->_loadFlashQueue();
        $id = $message->getId();

        unset($this->_flashQueue->instant[$id], $this->_flashQueue->queued[$id]);
        
        if($instantIfSpace && count($this->_flashQueue->instant) < $this->_flashQueue->limit) {
            $this->_flashQueue->instant[$id] = $message;
        } else {
            $this->_flashQueue->queued[$id] = $message;
        }
        
        return $this;
    }
    
    public function addInstantFlash(IFlashMessage $message) {
        return $this->queueFlash($message, true);
    }
    
    public function getInstantFlashes() {
        $this->_loadFlashQueue();
        return $this->_flashQueue->instant;
    }
    
    public function removeQueuedFlash($id) {
        $this->_loadFlashQueue();

        unset(
            $this->_flashQueue->constant[$id],
            $this->_flashQueue->instant[$id]
        );
        
        return $this;
    }
}