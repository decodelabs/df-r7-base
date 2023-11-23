<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\flow;

use df\core;
use df\flow;
use df\user;

interface IManager extends core\IManager
{
    // Mail
    public function sendMail(flow\mail\IMessage $message, flow\mail\ITransport $transport = null);
    public function forceSendMail(flow\mail\IMessage $message, flow\mail\ITransport $transport = null);

    public function getDefaultMailTransportName($forceSend = false);
    public function getMailModel();


    // Lists
    public function getListSources(): array;
    public function getListSource($id): ?flow\mailingList\ISource;
    public function hasListSource(string $id): bool;
    public function getListManifest(): array;
    public function getAvailableListAdapters(): array;
    public function getListAdapterSettingsFields(string $adapter): array;
    public function getListOptions(): array;
    public function getListGroupOptions(): array;
    public function refreshListCache(): void;

    public function getListExternalLinkFor($source, string $listId = null): ?string;

    public function getGroupSetOptionsFor($source, ?string $listId): array;
    public function getGroupOptionsFor($source, ?string $listId, bool $nested = false, bool $showSets = true): array;
    public function getGroupIdListFor($source, ?string $listId): array;
    public function getPrimaryGroupSetOptionsFor($source): array;
    public function getPrimaryGroupOptionsFor($source, bool $nested = false, bool $showSets = true): array;
    public function getPrimaryGroupIdListFor($source): array;

    public function subscribeClientToPrimaryList(
        $source,
        array $groups = null,
        bool $replace = false,
        ?array $extraData = null,
        ?array $tags = null
    ): flow\mailingList\ISubscribeResult;

    public function subscribeClientToList(
        $source,
        $listId,
        array $groups = null,
        bool $replace = false,
        ?array $extraData = null,
        ?array $tags = null
    ): flow\mailingList\ISubscribeResult;

    public function subscribeClientToGroups(
        array $compoundGroupIds,
        bool $replace = false,
        ?array $extraData = null,
        ?array $tags = null
    ): array;

    public function subscribeUserToPrimaryList(
        user\IClientDataObject $client,
        $source,
        array $groups = null,
        bool $replace = false,
        ?array $extraData = null,
        ?array $tags = null
    ): flow\mailingList\ISubscribeResult;

    public function subscribeUserToList(
        user\IClientDataObject $client,
        $source,
        $listId,
        array $groups = null,
        bool $replace = false,
        ?array $extraData = null,
        ?array $tags = null
    ): flow\mailingList\ISubscribeResult;

    public function subscribeUserToGroups(
        user\IClientDataObject $client,
        array $compoundGroupIds,
        bool $replace = false,
        ?array $extraData = null,
        ?array $tags = null
    ): array;

    public function getClientSubscribedGroups(): array;
    public function getClientSubscribedGroupsFor($source): array;
    public function getClientSubscribedGroupsIn($source, ?string $listId): array;
    public function getClientSubscribedPrimaryGroupsFor($source): array;
    public function isClientSubscribed($source, string $listId = null, string $groupId = null): bool;

    public function updateListUserDetails(string $oldEmail, user\IClientDataObject $client);

    public function unsubscribeClientFromPrimaryList($sourceId);
    public function unsubscribeClientFromList($sourceId, $listId);
    public function unsubscribeUserFromPrimaryList(user\IClientDataObject $client, $sourceId);
    public function unsubscribeUserFromList(user\IClientDataObject $client, $sourceId, string $listId);

    // Flash
    public function setFlashLimit($limit);
    public function getFlashLimit();

    public function newFlashMessage($id, $message = null, $type = null);
    public function processFlashQueue();

    public function flash($id, $message = null, $type = null);
    public function flashNow($id, $message = null, $type = null);
    public function flashAlways($id, $message = null, $type = null);

    public function addConstantFlash(IFlashMessage $message);
    public function getConstantFlash($id);
    public function getConstantFlashes();
    public function removeConstantFlash($id);
    public function clearConstantFlashes();

    public function queueFlash(IFlashMessage $message, $instantIfSpace = false);
    public function addInstantFlash(IFlashMessage $message);
    public function getInstantFlashes();
    public function removeQueuedFlash($id);
}


interface IFlashMessage
{
    public const INFO = 'info';
    public const SUCCESS = 'success';
    public const ERROR = 'error';
    public const WARNING = 'warning';
    public const DEBUG = 'debug';

    public function getId(): string;
    public function setType($type);
    public function getType();
    public function isDebug();

    public function isDisplayed(bool $flag = null);
    public function setMessage($message);
    public function getMessage();
    public function setDescription($description);
    public function getDescription();

    public function setLink($link, $text = null);
    public function getLink();
    public function setLinkText($text);
    public function getLinkText();
    public function clearLink();
    public function shouldLinkOpenInNewWindow(bool $flag = null);
}



class FlashQueue implements \Serializable
{
    public $limit = 15;
    public $constant = [];
    public $queued = [];
    public $instant = [];

    public function isEmpty(): bool
    {
        return empty($this->constant) &&
            empty($this->queued) &&
            empty($this->instant);
    }

    public function serialize()
    {
        return serialize($this->__serialize());
    }

    public function __serialize(): array
    {
        $data = ['l' => $this->limit];

        if (!empty($this->constant)) {
            $data['c'] = $this->constant;
        }

        if (!empty($this->queued)) {
            $data['q'] = $this->queued;
        }

        if (!empty($this->instant)) {
            $data['i'] = $this->instant;
        }

        return $data;
    }

    public function unserialize(string $data): void
    {
        $data = unserialize($data);
        $this->__unserialize($data);
    }

    public function __unserialize(array $data): void
    {
        $this->limit = $data['l'];

        if (isset($data['c'])) {
            $this->constant = $data['c'];
        }

        if (isset($data['q'])) {
            $this->queued = $data['q'];
        }

        if (isset($data['i'])) {
            $this->instant = $data['i'];
        }
    }
}
