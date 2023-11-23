<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\flow\mailingList\adapter;

use DecodeLabs\Exceptional;
use df\axis;
use df\core;
use df\flow;
use df\spur;

use df\user;

class Mailchimp3 extends Base
{
    public const SETTINGS_FIELDS = ['*apiKey' => 'API key'];

    protected $_mediator;
    protected $_memberUnit;

    protected function __construct(core\collection\ITree $options)
    {
        if (!$apiKey = $options['apiKey']) {
            throw Exceptional::{'df/flow/mailingList/Setup'}(
                'Mailchimp apiKey has not been set'
            );
        }

        $this->_mediator = new spur\mail\mailchimp3\Mediator($apiKey);
        $this->_memberUnit = axis\Model::loadUnitFromId('mailingList/member');
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


    public function subscribeUserToList(
        user\IClientDataObject $client,
        string $listId,
        array $manifest,
        array $groups = null,
        bool $replace = false,
        ?array $extraData = null,
        ?array $tags = null
    ): flow\mailingList\ISubscribeResult {
        $email = $client->getEmail();
        $merges = [];
        $tags ??= [];
        $result = new flow\mailingList\SubscribeResult();

        if (!$email) {
            return $result;
        }

        $result->setEmailAddress($email, $client->getFullName());
        $interests = [];

        if ($member = $this->_getMemberData($listId, $email)) {
            //dd($member);
            $merges = $member['mergeFields'];

            if (!$replace) {
                $interests = $member['interests'];
                $tags = array_unique($tags + array_values($member['tags'] ?? []));
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
            $this->_mediator->ensureSubscription($listId, $client, $interests, $extraData, $tags);

            $result
                ->isSuccessful(true)
                ->isSubscribed(true);
        } catch (spur\mail\mailchimp3\ApiException $e) {
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
            if ($id = $client->getId()) {
                $this->_memberUnit->remove('mailchimp', $listId, $id);
            }
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
                if (
                    !isset($memberData['interests'][$groupId]) ||
                    !$memberData['interests'][$groupId]
                ) {
                    continue;
                }

                $output[$listId][$groupId] = $group['name'];
            }
        }

        return $output;
    }

    public function refreshClientManifest(): void
    {
        $userId = user\Manager::getInstance()->getId();

        if ($userId) {
            $this->_memberUnit->purge('mailchimp', $userId);
        }
    }

    public function updateListUserDetails(string $oldEmail, user\IClientDataObject $client, array $manifest)
    {
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

            if ($id = $client->getId()) {
                $this->_memberUnit->remove('mailchimp', $listId, $id);
            }
        }

        return $this;
    }

    public function unsubscribeUserFromList(user\IClientDataObject $client, string $listId)
    {
        $this->_mediator->unsubscribe($listId, $client->getEmail());

        if ($id = $client->getId()) {
            $this->_memberUnit->remove('mailchimp', $listId, $id);
        }

        return $this;
    }


    protected function _getClientMemberData(string $listId): ?array
    {
        $userManager = user\Manager::getInstance();

        if (!$userManager->isLoggedIn()) {
            return null;
        }

        $userId = $userManager->getId();

        return $this->_memberUnit->get('mailchimp', $listId, $userId, function () use ($userManager, $listId) {
            return $this->_fetchMemberData($listId, $userManager->getClient()->getEmail());
        });
    }

    protected function _getMemberData(string $listId, string $email = null): ?array
    {
        $clientEmail = user\Manager::getInstance()->getClient()->getEmail();

        if ($email == $clientEmail) {
            return $this->_getClientMemberData($listId);
        }

        if ($email === null) {
            $email = $clientEmail;
        }

        return $this->_fetchMemberData($listId, $email);
    }

    protected function _fetchMemberData(string $listId, string $email): ?array
    {
        try {
            $member = $this->_mediator->fetchMember($listId, $email);

            if ($member['status'] == 'unsubscribed') {
                return null;
            }
        } catch (\Throwable $e) {
            return null;
        }



        // Interest
        $interests = $member->interests->toArray();

        foreach ($interests as $key => $enabled) {
            if (!$enabled) {
                unset($interests[$key]);
            }
        }

        // Tags
        $tags = [];

        foreach ($member->tags->toArray() as $tag) {
            $tags[$tag['id']] = $tag['name'];
        }

        return [
            'id' => $member['id'],
            'email' => $member['email_address'],
            'emailId' => $member['unique_email_id'],
            'status' => $member['status'],
            'mergeFields' => $member->merge_fields->toArray(),
            'interests' => $interests,
            'tags' => $tags
        ];
    }
}
