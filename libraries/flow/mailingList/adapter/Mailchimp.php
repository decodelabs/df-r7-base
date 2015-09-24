<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flow\mailingList\adapter;

use df;
use df\core;
use df\flow;
use df\spur;
use df\user;
use df\link;

class Mailchimp extends Base {
    
    public static $optionFields = [
        '*apiKey' => 'API key'
    ];

    protected $_mediator;

    protected function __construct(core\collection\ITree $options) {
        if(!$apiKey = $options['apiKey']) {
            throw new flow\mailingList\RuntimeException(
                'Mailchimp apiKey has not been set'
            );
        }

        $this->_mediator = new spur\mail\mailchimp\Mediator($apiKey);
    }

    public function canConnect() {
        try {
            $lists = $this->_mediator->fetchAllLists();
            return true;
        } catch(\Exception $e) {
            return false;
        }
    }

    public function fetchManifest() {
        $output = [];

        foreach($this->_mediator->fetchAllLists() as $listId => $list) {
            $row = [
                'name' => $list->getName(),
                'groupSets' => [],
                'groups' => [],
                'subscribers' => $list->countMembers()
            ];

            foreach($list->fetchGroupSets() as $setId => $set) {
                $row['groupSets'][$setId] = $set->getName();

                foreach($set->getGroups() as $group) {
                    $row['groups'][$group->getCompoundId()] = [
                        'name' => $group->getName(),
                        'groupSet' => $setId,
                        'subscribers' => $group->countSubscribers()
                    ];
                }
            }

            $output[$listId] = $row;
        }

        return $output;
    }


    public function subscribeUserToList(user\IClientDataObject $client, $listId, array $manifest, array $groups=null, $replace=false) {
        $email = $client->getEmail();
        $merges = [];

        if(!$email) {
            return $this;
        }

        $memberGroupData = null;

        if($member = $this->_getMemberData($listId, $email)) {
            $merges = $member->getMergeData();
            unset($merges['EMAIL']);

            if(!$replace) {
                $memberGroupData = $member->getGroupSetData();
            }
        } else {
            if($firstName = $client->getFirstName()) {
                $merges['FNAME'] = $firstName;
            }

            if($surname = $client->getSurname()) {
                $merges['LNAME'] = $surname;
            }
        }

        if(!isset($merges['MC_LANGUAGE'])) {
            $merges['MC_LANGUAGE'] = $client->getLanguage();
        }

        $country = null;

        if(!isset($merges['MC_LOCATION']) && df\Launchpad::$application instanceof core\application\Http) {
            $ip = df\Launchpad::$application->getHttpRequest()->getIp();
            $geoIp = link\geoIp\Handler::factory()->lookup($ip);

            if($geoIp->hasLatLong()) {
                $merges['MC_LOCATION'] = [
                    'LONGITUDE' => $geoIp->longitude,
                    'LATITUDE' => $geoIp->latitude
                ];
            }

            $country = $geoIp->country;
        }

        if(!$country) {
            $country = $client->getCountry();
        }

        if(!empty($country)) {
            $merges['COUNTRY'] = $client->getCountry();
        }

        if(!$groups) {
            $groups = [];
        }

        $availableGroups = [];

        if(!empty($groups) || $memberGroupData) {
            $availableGroups = $manifest['groups'];
        }

        if(!empty($groups)) {
            foreach($groups as $i => $groupId) {
                if(isset($availableGroups[$groupId])) {
                    $group = $availableGroups[$groupId];
                    $groups[$group['groupSet']][] = $group['name'];
                }

                unset($groups[$i]);
            }
        }

        if($memberGroupData) {
            foreach($memberGroupData as $setId => $memberGroups) {
                foreach($memberGroups as $memberGroup) {
                    if(!isset($groups[$setId]) || !is_array($groups[$setId])) {
                        $groups[$setId] = [];
                    }

                    if(!in_array($memberGroup, $groups[$setId])) {
                        $groups[$setId][] = $memberGroup;
                    }
                }
            }
        }

        if(!empty($availableGroups)) {
            foreach($availableGroups as $groupId => $group) {
                $setId = $group['groupSet'];

                if(!isset($groups[$setId])) {
                    $groups[$setId] = $setId;
                }
            }
        }
        
        $this->_mediator->ensureSubscription($listId, $email, $merges, $groups);
        
        $cache = flow\mailingList\Cache::getInstance();
        $cache->clearSession();

        return $this;
    }




    protected function _getClientMemberData($listId) {
        $sessionKey = 'mailchimp:member:'.$listId;
        $cache = flow\mailingList\Cache::getInstance();

        if(null === ($member = $cache->getSession($sessionKey))) {
            $member = $this->_getMemberData($listId);
            $cache->setSession($sessionKey, $member ? $member : false);
        }

        return $member;
    }

    protected function _getMemberData($listId, $email=null) {
        $clientEmail = user\Manager::getInstance()->getClient()->getEmail();

        if($email == $clientEmail) {
            return $this->_getClientMemberData($listId);
        }

        if($email === null) {
            $email = $clientEmail;
        }

        try {
            return $this->_mediator->fetchMember($listId, $email);
        } catch(\Exception $e) {
            return null;
        }
    }
}