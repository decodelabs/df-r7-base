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
    protected $_flashQueueChanged = false;

    public function onApplicationShutdown(): void {
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
        $context = new core\SharedContext();

        try {
            // Grabbing body ensures preparation
            $bodyText = $message->getBodyText();
            $bodyHtml = $message->getBodyHtml();

            $userManager = user\Manager::getInstance();
            $userModel = $userManager->getUserModel();

            $message = clone $message;
            $to = $message->getToAddresses();
            $userList = $message->getToUsers();
            $keys = [];
            $isJustToAdmins = false;
            $config = flow\mail\Config::getInstance();

            // Admins
            if($message->shouldSendToAdmin()) {
                $isJustToAdmins = $to->isEmpty();

                foreach($config->getAdminAddresses() as $address) {
                    $to->add($address);
                }
            }

            // Users
            foreach($userList as $key => $user) {
                $isJustToAdmins = false;

                if($user === null) {
                    $keys[] = $key;
                } else {
                    $to->add($user['email'], $user['name']);
                }
            }

            // Clients
            $clientList = $userModel->getClientDataList($keys, array_keys($to->toArray()));
            $client = $userManager->client;

            foreach($clientList as $user) {
                $to->add($user->getEmail(), $user->getFullName());
            }

            if($message->shouldFilterClient()) {
                $to->remove($client->getEmail());
            }


            if($to->isEmpty()) {
                return $this;
            }

            $message->clearToUsers();

            $isWin = (0 === strpos(PHP_OS, 'WIN'));

            // From
            if(!$from = $message->getFromAddress()) {
                $from = flow\mail\Address::factory($config->getDefaultAddress());

                if(!$from->getName()) {
                    $from->setName(df\Launchpad::$application->getName());
                }

                $message->setFromAddress($from);
            }

            if(!$from->isValid()) {
                $context->logs->logException(core\Error::EValue(
                    'Invalid from address: '.$from
                ));

                return $this;
            }

            // Mime
            $mime = new flow\mime\MultiPart(flow\mime\IMultiPart::RELATED, [
                'MIME-Version' => '1.0',
                'Date' => core\time\Date::factory('now')->format(core\time\IDate::RFC2822),
                'Subject' => $message->getSubject(),
                'From' => $isWin ? $from->getAddress() : $from->toString()
            ]);

            $headers = $mime->getHeaders();
            $domain = null;

            if(isset($_SERVER['SERVER_NAME'])) {
                $domain = $_SERVER['SERVER_NAME'];
            } else {
                if($url = core\application\http\Config::getInstance()->getRootUrl()) {
                    $domain = df\link\http\Url::factory($url)->getDomain();
                }
            }

            if($domain) {
                $headers->set('Message-Id', sprintf(
                    "<%s.%s@%s>",
                    base_convert(microtime(), 10, 36),
                    base_convert(bin2hex(openssl_random_pseudo_bytes(8)), 16, 36),
                    $domain
                ));
            }

            if(!$returnPath = $message->getReturnPath()) {
                $message->setReturnPath($config->getDefaultReturnPath());
                $returnPath = $message->getReturnPath();
            }

            if($returnPath) {
                $headers->set('Return-Path', $returnPath->getAddress());
            }

            if($replyTo = $message->getReplyToAddress()) {
                $headers->set('Reply-To', $replyTo->getAddress());
            }

            // To
            $headers->set('to', (string)$to);

            if($message->hasCcAddresses()) {
                $headers->set('cc', (string)$message->getCcAddresses());
            }

            if($message->hasBccAddresses()) {
                $headers->set('bcc', (string)$message->getBccAddresses());
            }

            // Body
            if($bodyText === null) {
                if($bodyHtml === null) {
                    return $this;
                }

                /*
                // Turn this on when it works properly :)
                $bodyText = $context->html->toText($bodyHtml);
                $message->setBodyText($bodyText);
                */
            }

            $part = $mime->newMultiPart(flow\mime\IMultiPart::ALTERNATIVE);

            if($bodyText !== null) {
                $part->newContentPart($bodyText)
                    ->setContentType('text/plain')
                    ->setEncoding(flex\IEncoding::QP);
            }

            if($bodyHtml !== null) {
                $part->newContentPart($bodyHtml)
                    ->setContentType('text/html')
                    ->setEncoding(flex\IEncoding::QP);
            }


            // Attachments
            foreach($message->getAttachments() as $attachment) {
                $mime->newContentPart($attachment->getFile())
                    ->setContentType($attachment->getContentType())
                    ->setDisposition('attachment')
                    ->setFileName($attachment->getFileName())
                    ->setEncoding('BASE64')
                    ->getHeaders()
                        ->set('X-Attachment-Id', $attachment->getContentId())
                        ->set('Content-Id', '<'.$attachment->getContentId().'>');
            }


            // Send
            if(!$forceSend && $message->shouldForceSend()) {
                $forceSend = true;
            }

            $isDefault = false;
            $transportName = null;

            if($transport === null) {
                $transportName = $this->getDefaultMailTransportName($forceSend);
                $transport = flow\mail\transport\Base::factory($transportName);
                $isDefault = true;
            }

            try {
                $output = $transport->send($message, $mime);
            } catch(\Throwable $e) {
                if($isDefault && $transportName !== 'Mail' && $transportName !== 'Capture') {
                    $context->logs->logException($e);
                    $transport = flow\mail\transport\Base::factory('Mail');
                    $output = $transport->send($message, $mime);
                } else {
                    throw $e;
                }
            }

            if($message->shouldJournal()) {
                try {
                    $model = $this->getMailModel();
                    $model->journalMail($message);
                } catch(\Throwable $e) {
                    $context->logs->logException($e);
                }
            }

            return $output;
        } catch(\Throwable $e) {
            if($context->application->isDevelopment()) {
                throw $e;
            } else {
                $context->logs->logException($e);
            }
        }

        return false;
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
            throw core\Error::{'flow/mail/EDefinition'}(
                'Mail model does not implement flow\\mail\\IMailModel'
            );
        }

        return $model;
    }


## LISTS
    public function getListSources() {
        $config = flow\mail\Config::getInstance();
        $output = [];

        foreach($config->getListSources() as $id => $options) {
            try {
                $source = new flow\mailingList\Source($id, $options);
            } catch(flow\mailingList\IError $e) {
                core\logException($e);
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
        } catch(flow\mailingList\IError $e) {
            return null;
        }
    }

    public function hasListSource($id) {
        $config = flow\mail\Config::getInstance();
        $options = $config->getListSource($id);

        return isset($options->adapter);
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

    public function clearListCache() {
        flow\mailingList\Cache::getInstance()->clearGlobal();
        flow\mailingList\ApiStore::getInstance()->clear();
        return $this;
    }




    public function getListExternalLinkFor($sourceId) {
        if($source = $this->getListSource($sourceId)) {
            return $source->getListExternalLink();
        }
    }


    public function getPrimaryGroupSetOptionsFor($sourceId) {
        if(!$source = $this->getListSource($sourceId)) {
            return [];
        }

        if(!$listId = $source->getPrimaryListId()) {
            return [];
        }

        return $source->getGroupSetOptionsFor($listId);
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



    public function subscribeClientToPrimaryList($sourceId, array $groups=null, $replace=false): flow\mailingList\ISubscribeResult {
        $client = user\Manager::getInstance()->getClient();
        return $this->subscribeUserToPrimaryList($client, $sourceId, $groups, $replace);
    }

    public function subscribeClientToList($sourceId, $listId, array $groups=null, $replace=false): flow\mailingList\ISubscribeResult  {
        $client = user\Manager::getInstance()->getClient();
        return $this->subscribeUserToList($client, $sourceId, $listId, $groups, $replace);
    }

    public function subscribeClientToGroups(array $compoundGroupIds, $replace=false): array  {
        $client = user\Manager::getInstance()->getClient();
        return $this->subscribeUserToGroups($client, $compoundGroupIds, $replace);
    }

    public function subscribeUserToPrimaryList(user\IClientDataObject $client, $sourceId, array $groups=null, $replace=false): flow\mailingList\ISubscribeResult  {
        if(!$source = $this->getListSource($sourceId)) {
            throw core\Error::{'flow/mailingList/EApi,flow/mailingList/ENotFound'}(
                'List source '.$sourceId.' does not exist'
            );
        }

        if(!$listId = $source->getPrimaryListId()) {
            throw core\Error::{'flow/mailingList/EApi,flow/mailingList/ENotFound'}(
                'No primary list has been set for mailing list source '.$source->getId()
            );
        }

        return $this->subscribeUserToList($client, $source, $listId, $groups, $replace);
    }

    public function subscribeUserToList(user\IClientDataObject $client, $sourceId, $listId, array $groups=null, $replace=false): flow\mailingList\ISubscribeResult  {
        if(!$source = $this->getListSource($sourceId)) {
            throw core\Error::{'flow/mailingList/EApi,flow/mailingList/ENotFound'}(
                'List source '.$sourceId.' does not exist'
            );
        }

        return $source->subscribeUserToList($client, $listId, $groups, $replace);
    }

    public function subscribeUserToGroups(user\IClientDataObject $client, array $compoundGroupIds, $replace=false): array  {
        $manifest = $output = [];

        foreach($compoundGroupIds as $id) {
            list($sourceId, $listId, $groupId) = explode('/', $id);
            $manifest[$sourceId][$listId][] = $groupId;
        }

        foreach($manifest as $sourceId => $lists) {
            foreach($lists as $listId => $groups) {
                $output[$sourceId][$listId] = $this->subscribeClientToList($client, $sourceId, $listId, $groups, $replace);
            }
        }

        return $output;
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

    public function isClientSubscribed($sourceId, $listId=null, $groupId=null): bool {
        if(!$source = $this->getListSource($sourceId)) {
            return false;
        }

        if($listId === null) {
            $listId = $source->getPrimaryListId();
        }

        if(!$listId) {
            return false;
        }

        try {
            $manifest = $source->getClientManifest();
        } catch(\Throwable $e) {
            core\logException($e);
            return false;
        }

        if(!isset($manifest[$listId])) {
            return false;
        }

        if($groupId === null) {
            if(($manifest[$listId] ?? false) === false) {
                return false;
            }

            return true;
        }

        return $manifest[$listId][$groupId] ?? false;
    }


    public function updateListUserDetails(string $oldEmail, user\IClientDataObject $client) {
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

    public function unsubscribeUserFromList(user\IClientDataObject $client, $sourceId, string $listId) {
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
        if($this->_flashDisabled) {
            return $this;
        }

        if(!$this->_isFlashQueueProcessed) {
            $this->_loadFlashQueue();

            if($this->_flashDisabled) {
                return $this;
            }

            foreach($this->_flashQueue->instant as $id => $message) {
                if($message->isDisplayed()) {
                    if($message->canDisplayAgain()) {
                        $message->resetDisplayState();
                    } else {
                        unset($this->_flashQueue->instant[$id]);
                    }

                    $this->_flashQueueChanged = true;
                }
            }

            $limit = $this->_flashQueue->limit - count($this->_flashQueue->instant);

            for($i = 0; $i < $limit; $i++) {
                if(!$message = array_shift($this->_flashQueue->queued)) {
                    break;
                }

                $this->_flashQueue->instant[$message->getId()] = $message;
                $this->_flashQueueChanged = true;
            }

            $this->_isFlashQueueProcessed = true;
        }

        return $this;
    }

    private $_flashDisabled = null;

    protected function _loadFlashQueue() {
        if($this->_flashDisabled) {
            return;
        }

        if($this->_flashQueue === null) {
            if($this->_flashDisabled === null) {
                $context = df\arch\Context::getCurrent();

                if($context->getRunMode() == 'Http' && !$context->http->isAjaxRequest()) {
                    $this->_flashDisabled = false;
                } else {
                    $this->_flashDisabled = true;
                    return;
                }
            }

            $session = user\Manager::getInstance()->session->getBucket(self::SESSION_NAMESPACE);
            $this->_flashQueue = $session->get(self::FLASH_SESSION_KEY);

            if(!$this->_flashQueue instanceof FlashQueue) {
                $this->_flashQueue = new FlashQueue();
                $this->_flashQueueChanged = true;
            }
        }
    }

    protected function _saveFlashQueue() {
        if($this->_flashDisabled) {
            return false;
        }

        if(!$this->_flashQueue instanceof FlashQueue
        || !$this->_flashQueueChanged) {
            return false;
        }

        $session = user\Manager::getInstance()->session->getBucket(self::SESSION_NAMESPACE);
        $session->set(self::FLASH_SESSION_KEY, $this->_flashQueue);
        $this->_flashQueueChanged = false;

        return true;
    }


// Shortcuts
    public function flash($id, $message=null, $type=null) {
        $message = $this->newFlashMessage($id, $message, $type);

        if(!$this->_flashDisabled) {
            $this->queueFlash($message);
        }

        return $message;
    }

    public function flashNow($id, $message=null, $type=null) {
        $message = $this->newFlashMessage($id, $message, $type);

        if(!$this->_flashDisabled) {
            $this->addInstantFlash($message);
        }

        return $message;
    }

    public function flashAlways($id, $message=null, $type=null) {
        $message = $this->newFlashMessage($id, $message, $type);

        if(!$this->_flashDisabled) {
            $this->addConstantFlash($message);
        }

        return $message;
    }


// Constant
    public function addConstantFlash(IFlashMessage $message) {
        $this->_loadFlashQueue();

        if(!$this->_flashDisabled) {
            $this->_flashQueue->constant[$message->getId()] = $message;
            $this->_flashQueueChanged = true;
        }

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

        if($this->_flashDisabled) {
            return [];
        }

        return $this->_flashQueue->constant;
    }

    public function removeConstantFlash($id) {
        $this->_loadFlashQueue();

        if(!$this->_flashDisabled) {
            unset($this->_flashQueue->constant[$id]);
            $this->_flashQueueChanged = true;
        }

        return $this;
    }

    public function clearConstantFlashes() {
        $this->_loadFlashQueue();

        if(!$this->_flashDisabled) {
            $this->_flashQueue->constant = [];
            $this->_flashQueueChanged = true;
        }

        return $this;
    }

// Queued
    public function queueFlash(IFlashMessage $message, $instantIfSpace=false) {
        $this->_loadFlashQueue();

        if($this->_flashDisabled) {
            return $this;
        }

        $id = $message->getId();

        unset($this->_flashQueue->instant[$id], $this->_flashQueue->queued[$id]);

        if($instantIfSpace && count($this->_flashQueue->instant) < $this->_flashQueue->limit) {
            $this->_flashQueue->instant[$id] = $message;
        } else {
            $this->_flashQueue->queued[$id] = $message;
        }

        $this->_flashQueueChanged = true;

        return $this;
    }

    public function addInstantFlash(IFlashMessage $message) {
        return $this->queueFlash($message, true);
    }

    public function getInstantFlashes() {
        $this->_loadFlashQueue();

        if($this->_flashDisabled) {
            return [];
        }

        return $this->_flashQueue->instant;
    }

    public function removeQueuedFlash($id) {
        $this->_loadFlashQueue();

        if(!$this->_flashDisabled) {
            unset(
                $this->_flashQueue->constant[$id],
                $this->_flashQueue->instant[$id]
            );

            $this->_flashQueueChanged = true;
        }

        return $this;
    }
}
