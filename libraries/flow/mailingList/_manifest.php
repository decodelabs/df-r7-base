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

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}


// Interfaces
interface ISource {
    public function getId();
    public function getAdapter();
    public function canConnect();

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

    public function subscribeUserToList(user\IClientDataObject $client, $listId, array $groups=null, $replace=false);

    public function getClientManifest();
    public function getClientSubscribedGroupsIn($listId);

    public function updateListUserDetails($oldEmail, user\IClientDataObject $client);
    public function unsubscribeUserFromList(user\IClientDataObject $client, $listId);
}

interface IAdapter {
    public static function getSettingsFields();
    public function getName();
    public function getId();
    public function canConnect();
    public function fetchManifest();

    public function subscribeUserToList(user\IClientDataObject $client, $listId, array $manifest, array $groups=null, $replace=false);
    public function fetchClientManifest(array $manifest);
    public function updateListUserDetails($oldEmail, user\IClientDataObject $client, array $manifest);
    public function unsubscribeUserFromList(user\IClientDataObject $client, $listId);
}



class Cache extends core\cache\SessionExtended {}