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

use DecodeLabs\Glitch;

class Mailchimp3 extends Base
{
    const SETTINGS_FIELDS = ['*apiKey' => 'API key'];

    protected $_mediator;

    protected function __construct(core\collection\ITree $options)
    {
        if (!$apiKey = $options['apiKey']) {
            throw Glitch::{'df/flow/mailingList/ESetup'}(
                'Mailchimp apiKey has not been set'
            );
        }

        $this->_mediator = new spur\mail\mailchimp3\Mediator($apiKey);
    }

    public function getId(): string
    {
        return $this->_mediator->getApiKey();
    }

    public function canConnect(): bool
    {
        return $this->_mediator->canConnect();
    }

    public function fetchManifest(): array
    {
        $output = [];

        $lists = $this->_mediator->fetchLists(
            $this->_mediator->newListFilter()
                ->setFields('id', 'name', 'subscribe_url_short', 'stats.member_count')
                ->setLimit(100)
        );

        foreach ($lists as $list) {
            $row = [
                'name' => $list['name'],
                'url' => $list['subscribe_url_short'],
                'groupSets' => [],
                'groups' => [],
                'subscribers' => $list->stats['member_count']
            ];

            $categories = $this->_mediator->fetchInterestCategories(
                $list['id'],
                $this->_mediator->newInterestCategoryFilter()
                    ->setFields('id', 'title')
                    ->setLimit(100)
            );

            foreach ($categories as $category) {
                $row['groupSets'][$category['id']] = $category['title'];

                $interests = $this->_mediator->fetchInterests(
                    $list['id'],
                    $category['id'],
                    $this->_mediator->newInterestFilter()
                        ->setFields('id', 'name', 'subscriber_count')
                        ->setLimit(100)
                );

                foreach ($interests as $interest) {
                    $row['groups'][$interest['id']] = [
                        'name' => $interest['name'],
                        'groupSet' => $category['id'],
                        'subscribers' => $interest['subscriber_count']
                    ];
                }
            }

            $output[$list['id']] = $row;
        }

        return $output;
    }


    public function subscribeUserToList(user\IClientDataObject $client, string $listId, array $manifest, array $groups=null, bool $replace=false): flow\mailingList\ISubscribeResult
    {
        $email = $client->getEmail();
        $merges = [];
        $result = new flow\mailingList\SubscribeResult();

        if (!$email) {
            return $result;
        }

        $result->setEmailAddress($email, $client->getFullName());
        $interests = [];

        if ($member = $this->_getMemberData($listId, $email)) {
            $merges = $member['mergeFields'];

            if (!$replace) {
                $interests = $member['interests'];
            }
        }

        if ($replace) {
            foreach ($manifest['groups'] as $groupId => $group) {
                $interests[$groupId] = false;
            }
        }

        if (!empty($groups)) {
            foreach ($groups as $groupId) {
                if (isset($manifest['groups'][$groupId])) {
                    $interests[$groupId] = true;
                }
            }
        }

        $result
            ->isSubscribed($member ? $member['status'] != 'unsubscribed' : false)
            ->setManualInputUrl($manifest['url']);

        try {
            $this->_mediator->ensureSubscription($listId, $client, $interests);

            $result
                ->isSuccessful(true)
                ->isSubscribed(true);
        } catch (spur\mail\mailchimp3\EApi $e) {
            $result->isSuccessful(false);
            $handled = false;

            switch ($e->getCode()) {
                case 400:
                    if (preg_match('/fake or invalid/i', $e->getMessage())) {
                        $result->isInvalid(true);
                        $handled = true;
                    } elseif (preg_match('/not allowing more signups for now/i', $e->getMessage())) {
                        $result->isThrottled(true);
                        $handled = true;
                    } elseif (preg_match('/is in a compliance state/i', $e->getMessage())) {
                        $result->requiresManualInput(true);
                        $handled = true;
                    }

                    break;
            }

            if (!$handled) {
                core\logException($e);
            }
        }

        if ($result->isSuccessful()) {
            $cache = flow\mailingList\Cache::getInstance();
            $cache->removeSession('mailchimp3:'.$listId);
        }

        return $result;
    }

    public function fetchClientManifest(array $manifest): array
    {
        $output = [];

        foreach ($manifest as $listId => $list) {
            if (!$memberData = $this->_getClientMemberData($listId)) {
                $output[$listId] = false;
                continue;
            }

            $output[$listId] = [];

            foreach ($list['groups'] as $groupId => $group) {
                if (!isset($memberData['interests'][$groupId]) ||
                    !$memberData['interests'][$groupId]) {
                    continue;
                }

                $output[$listId][$groupId] = $group['name'];
            }
        }

        return $output;
    }

    public function updateListUserDetails(string $oldEmail, user\IClientDataObject $client, array $manifest)
    {
        $cache = flow\mailingList\Cache::getInstance();

        foreach ($manifest as $listId => $list) {
            if (!$member = $this->_getMemberData($listId, $oldEmail)) {
                continue;
            }

            if ($oldEmail != $client->getEmail()) {
                if ($clashMember = $this->_getMemberData($listId, $client->getEmail())) {
                    $this->_mediator->deleteMember($listId, $client->getEmail());
                }
            }

            $this->_mediator->updateMemberDetails($listId, $oldEmail, $client);
            $cache->removeSession('mailchimp3:'.$listId);
        }

        return $this;
    }

    public function unsubscribeUserFromList(user\IClientDataObject $client, string $listId)
    {
        $this->_mediator->unsubscribe($listId, $client->getEmail());

        $cache = flow\mailingList\Cache::getInstance();
        $cache->removeSession('mailchimp3:'.$listId);

        return $this;
    }


    protected function _getClientMemberData(string $listId): ?array
    {
        $sessionKey = 'mailchimp3:'.$listId;
        $cache = flow\mailingList\Cache::getInstance();

        if (null === ($member = $cache->getSession($sessionKey))) {
            $member = $this->_getMemberData($listId);
            $cache->setSession($sessionKey, $member ?? false);
        }

        return $member ? $member : null;
    }

    protected function _getMemberData(string $listId, string $email=null): ?array
    {
        $clientEmail = user\Manager::getInstance()->getClient()->getEmail();

        if ($email == $clientEmail) {
            return $this->_getClientMemberData($listId);
        }

        if ($email === null) {
            $email = $clientEmail;
        }

        try {
            $member = $this->_mediator->fetchMember($listId, $email);

            if ($member['status'] == 'unsubscribed') {
                return null;
            }
        } catch (\Throwable $e) {
            return null;
        }

        return [
            'id' => $member['id'],
            'email' => $member['email_address'],
            'emailId' => $member['unique_email_id'],
            'status' => $member['status'],
            'mergeFields' => $member->merge_fields->toArray(),
            'interests' => $member->interests->toArray()
        ];
    }
}
