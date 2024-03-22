<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\flow\mailingList;

use df\flow;
use df\user;

// Interfaces
interface ISource
{
    public function getId(): string;
    public function getAdapter(): IAdapter;
    public function canConnect(): bool;

    public function getManifest(): array;
    public function getListManifest(?string $listId): ?array;
    public function getPrimaryListId(): ?string;
    public function getPrimaryListManifest(): ?array;

    public function getListExternalLink(?string $listId): ?string;
    public function getPrimaryListExternalLink(): ?string;

    public function getListOptions(): array;
    public function getGroupSetOptions(): array;
    public function getGroupOptions(bool $nested = false, bool $showSets = true): array;
    public function getGroupSetOptionsFor(?string $listId): array;
    public function getGroupOptionsFor(?string $listId, bool $nested = false, bool $showSets = true): array;
    public function getGroupIdListFor(?string $listId): array;

    public function subscribeUserToList(
        user\IClientDataObject $client,
        string $listId,
        array $groups = null,
        bool $replace = false,
        ?array $extraData = null,
        ?array $tags = null
    ): ISubscribeResult;

    public function getClientManifest(): array;
    public function getClientSubscribedGroupsIn(?string $listId): array;
    public function refreshClientManifest(): void;

    public function updateListUserDetails(string $oldEmail, user\IClientDataObject $client);
    public function unsubscribeUserFromList(user\IClientDataObject $client, string $listId);
}

interface IAdapter
{
    public static function getSettingsFields(): array;
    public function getName(): string;
    public function getId(): string;
    public function canConnect(): bool;
    public function fetchManifest(): array;

    public function subscribeUserToList(
        user\IClientDataObject $client,
        string $listId,
        array $manifest,
        array $groups = null,
        bool $replace = false,
        ?array $extraData = null,
        ?array $tags = null
    ): ISubscribeResult;
    public function fetchClientManifest(array $manifest): array;
    public function refreshClientManifest(): void;
    public function updateListUserDetails(string $oldEmail, user\IClientDataObject $client, array $manifest);
    public function unsubscribeUserFromList(user\IClientDataObject $client, string $listId);
}


interface ISubscribeResult
{
    public function isSuccessful(bool $flag = null);
    public function isSubscribed(bool $flag = null);

    public function requiresManualInput(bool $flag = null);
    public function setManualInputUrl(string $url = null);
    public function getManualInputUrl();

    public function setEmailAddress($address, $name = null);
    public function getEmailAddress(): ?flow\mail\IAddress;

    public function hasBounced(bool $flag = null);
    public function isInvalid(bool $flag = null);
    public function isThrottled(bool $flag = null);
}
