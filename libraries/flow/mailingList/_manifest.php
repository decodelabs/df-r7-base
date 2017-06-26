<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flow\mailingList;

use df;
use df\core;
use df\flow;
use df\spur;
use df\user;

// Interfaces
interface ISource {
    public function getId();
    public function getAdapter();
    public function canConnect(): bool;

    public function getManifest();
    public function getPrimaryListId();
    public function getPrimaryListManifest();

    public function getListExternalLink();

    public function getListOptions();
    public function getGroupSetOptions();
    public function getGroupOptions($nested=false, $showSets=true);
    public function getGroupSetOptionsFor($listId);
    public function getGroupOptionsFor($listId, $nested=false, $showSets=true);
    public function getGroupIdListFor($listId);

    public function subscribeUserToList(user\IClientDataObject $client, $listId, array $groups=null, $replace=false): ISubscribeResult;

    public function getClientManifest();
    public function getClientSubscribedGroupsIn($listId);

    public function updateListUserDetails(string $oldEmail, user\IClientDataObject $client);
    public function unsubscribeUserFromList(user\IClientDataObject $client, string $listId);
}

interface IAdapter {
    public static function getSettingsFields();
    public function getName();
    public function getId();
    public function canConnect(): bool;
    public function fetchManifest(): array;

    public function subscribeUserToList(user\IClientDataObject $client, $listId, array $manifest, array $groups=null, $replace=false): ISubscribeResult;
    public function fetchClientManifest(array $manifest): array;
    public function updateListUserDetails(string $oldEmail, user\IClientDataObject $client, array $manifest);
    public function unsubscribeUserFromList(user\IClientDataObject $client, string $listId);
}


interface ISubscribeResult {
    public function isSuccessful(bool $flag=null);
    public function isSubscribed(bool $flag=null);

    public function requiresManualInput(bool $flag=null);
    public function setManualInputUrl(string $url=null);
    public function getManualInputUrl();

    public function setEmailAddress($address, $name=null);
    public function getEmailAddress(): ?flow\mail\IAddress;

    public function hasBounced(bool $flag=null);
    public function isInvalid(bool $flag=null);
    public function isThrottled(bool $flag=null);
}


class Cache extends core\cache\SessionExtended {}
class ApiStore extends core\cache\FileStore {}
