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

    public function getAvailableLists();
    public function getAvailableGroupSetList();
    public function getAvailableGroupList();

    public function subscribeUserToList(user\IClientDataObject $client, $listId, array $groups=null, $replace=true);
}

interface IAdapter {
    public static function getOptionFields();
    public function getName();
    public function canConnect();
    public function fetchManifest();

    public function subscribeUserToList(user\IClientDataObject $client, $listId, array $manifest, array $groups=null, $replace=true);
}



class Cache extends core\cache\SessionExtended {}