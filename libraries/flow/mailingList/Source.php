<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flow\mailingList;

use df;
use df\core;
use df\flow;
use df\user;

class Source implements ISource
{
    const MANIFEST_VERSION = 100;

    protected $_id;
    protected $_adapter;
    protected $_primaryListId;

    public function __construct(string $id, $options)
    {
        $options = core\collection\Tree::factory($options);

        $this->_id = $id;
        $this->_adapter = flow\mailingList\adapter\Base::factory($options);
        $this->_primaryListId = $options['primaryList'];

        if ($this->_primaryListId !== null) {
            $this->_primaryListId = (string)$this->_primaryListId;
        }
    }

    public function getId(): string
    {
        return $this->_id;
    }

    public function getAdapter(): IAdapter
    {
        return $this->_adapter;
    }

    public function canConnect(): bool
    {
        return $this->_adapter->canConnect();
    }


    public function getManifest(): array
    {
        $cache = Cache::getInstance();

        if (!$manifest = $cache->get('manifest:'.$this->_id)) {
            $manifest = $this->_getManifestFromStore();

            if (($manifest['__manifest_version__'] ?? null) !== self::MANIFEST_VERSION) {
                $this->_clearManifestStore();
                $manifest = $this->_getManifestFromStore();
            }

            unset($manifest['__manifest_version__']);
            $cache->set('manifest:'.$this->_id, $manifest);
        }

        return $manifest;
    }

    protected function _getManifestFromStore(): array
    {
        $store = ApiStore::getInstance();

        if (!$manifestFile = $store->get($this->_id, '1 day')) {
            try {
                $manifest = $this->_adapter->fetchManifest();
            } catch (\Throwable $e) {
                throw core\Error::EApi([
                    'message' => 'Unable to fetch manifest from adapter',
                    'previous' => $e
                ]);
            }

            $manifest = $this->_normalizeManifest($manifest);
            $store->set($this->_id, serialize($manifest));
        } else {
            $manifest = unserialize($manifestFile->getContents());
        }

        return $manifest;
    }

    protected function _clearManifestStore(): void
    {
        $store = ApiStore::getInstance();
        $store->remove($this->_id);
    }

    protected function _normalizeManifest($manifest): array
    {
        if (!is_array($manifest)) {
            $manifest = [];
        }

        foreach ($manifest as $id => $list) {
            if (!isset($list['name'])) {
                $list['name'] = $this->_adapter->getName().': '.$list['id'];
            }

            if (!isset($list['groupSets'])) {
                $list['groupSets'] = [];
            }

            if (!isset($list['groups'])) {
                $list['groups'] = [];
            }

            if (!isset($list['url'])) {
                $list['url'] = null;
            }

            foreach ($list['groups'] as $groupId => $group) {
                if (!is_array($group)) {
                    $group = ['name' => (string)$group];
                }

                if (!isset($group['name'])) {
                    $group['name'] = 'Group: '.$groupId;
                }

                if (!isset($group['groupSet'])) {
                    $group['groupSet'] = null;
                }

                if (!isset($group['subscribers'])) {
                    $group['subscribers'] = null;
                }

                $list['groups'][$groupId] = $group;
            }

            if (!isset($list['subscribers'])) {
                $list['subscribers'] = null;
            }

            $manifest[$id] = $list;
        }

        $manifest['__manifest_version__'] = self::MANIFEST_VERSION;

        return $manifest;
    }


    public function getListManifest(?string $listId): ?array
    {
        if ($listId === null) {
            return null;
        }

        $manifest = $this->getManifest();

        if (isset($manifest[$listId])) {
            return $manifest[$listId];
        } else {
            return null;
        }
    }


    public function getPrimaryListId(): ?string
    {
        return $this->_primaryListId;
    }

    public function getPrimaryListManifest(): ?array
    {
        return $this->getListManifest($this->_primaryListId);
    }



    public function getListExternalLink(?string $listId): ?string
    {
        if (!$manifest = $this->getListManifest($listId)) {
            return null;
        }

        return $manifest['url'];
    }

    public function getPrimaryListExternalLink(): ?string
    {
        if (!$manifest = $this->getPrimaryListManifest()) {
            return null;
        }

        return $manifest['url'];
    }




    public function getListOptions(): array
    {
        $output = [];

        foreach ($this->getManifest() as $listId => $list) {
            $output[$listId] = $list['name'];
        }

        return $output;
    }

    public function getGroupSetOptions(): array
    {
        $output = [];

        foreach ($this->getManifest() as $listId => $list) {
            foreach ($list['groupSets'] as $setId => $setName) {
                $output[$listId.'/'.$setId] = $setName;
            }
        }

        return $output;
    }

    public function getGroupOptions(bool $nested=false, bool $showSets=true): array
    {
        $output = [];
        $manifest = $this->getManifest();
        $count = count($manifest);

        foreach ($manifest as $listId => $list) {
            foreach ($list['groups'] as $groupId => $group) {
                $cat = $list['name'];

                if ($showSets) {
                    $cat .= isset($list['groupSets'][$group['groupSet']]) ?
                        ' / '.$list['groupSets'][$group['groupSet']] : null;
                }

                if ($nested) {
                    $output[$cat][$listId.'/'.$groupId] = $group['name'];
                } else {
                    $output[$listId.'/'.$groupId] = $showSets ?
                        $cat.' / '.$group['name'] : $group['name'];
                }
            }
        }

        return $output;
    }

    public function getGroupSetOptionsFor(?string $listId): array
    {
        $manifest = $this->getManifest();

        if ($listId === null || !isset($manifest[$listId])) {
            return [];
        }

        $output = [];

        foreach ($manifest[$listId]['groupSets'] as $setId => $setName) {
            $output[$setId] = $setName;
        }

        return $output;
    }

    public function getGroupOptionsFor(?string $listId, bool $nested=false, bool $showSets=true): array
    {
        $manifest = $this->getManifest();

        if ($listId === null || !isset($manifest[$listId])) {
            return [];
        }

        $output = [];
        $list = $manifest[$listId];

        foreach ($list['groups'] as $groupId => $group) {
            $groupSet = $list['groupSets'][$group['groupSet']] ?? 'Default';

            if ($nested) {
                $output[$groupSet][$groupId] = $group['name'];
            } else {
                $output[$groupId] = $showSets ?
                    $groupSet.' / '.$group['name'] : $group['name'];
            }
        }

        return $output;
    }

    public function getGroupIdListFor(?string $listId): array
    {
        $manifest = $this->getManifest();

        if ($listId === null || !isset($manifest[$listId])) {
            return [];
        }

        return array_keys($manifest[$listId]['groups']);
    }



    public function subscribeUserToList(user\IClientDataObject $client, string $listId, array $groups=null, bool $replace=false): ISubscribeResult
    {
        $manifest = $this->getManifest();

        if (!isset($manifest[$listId])) {
            throw core\Error::{'EApi,ENotFound'}(
                'List id '.$listId.' could not be found on source '.$this->_id
            );
        }

        try {
            $result = $this->_adapter->subscribeUserToList($client, $listId, $manifest[$listId], $groups, $replace);
        } catch (\Throwable $e) {
            throw core\Error::EApi([
                'message' => 'Adapter failed to subscribe user to list',
                'previous' => $e,
                'data' => [
                    'list' => $listId,
                    'client' => $client
                ]
            ]);
        }

        if ($result->isSuccessful()) {
            $cache = flow\mailingList\Cache::getInstance();
            $cache->removeSession('client:'.$this->_id);
        }

        return $result;
    }



    public function getClientManifest(): array
    {
        return $this->_getClientManifest();
    }

    public function getClientSubscribedGroupsIn(?string $listId): array
    {
        if ($listId === null) {
            return [];
        }

        $clientManifest = $this->_getClientManifest([$listId]);

        if (isset($clientManifest[$listId]) && !empty($clientManifest[$listId])) {
            return $clientManifest[$listId];
        } else {
            return [];
        }
    }

    protected function _getClientManifest(array $listIds=null): array
    {
        $cache = Cache::getInstance();
        $manifest = $cache->getSession('client:'.$this->_id);
        $lists = null;

        if (!$manifest) {
            $manifest = [];
        }

        $selectIds = [];

        if ($listIds === null) {
            $lists = $this->getManifest();
            $listIds = array_keys($lists);
        }

        foreach ($listIds as $listId) {
            if (!array_key_exists($listId, $manifest)) {
                $selectIds[] = $listId;
            }
        }

        if (!empty($selectIds)) {
            if ($lists === null) {
                $lists = $this->getManifest();
            }

            $lists = array_intersect_key($lists, array_flip($selectIds));

            try {
                $manifest = array_merge($manifest, $this->_adapter->fetchClientManifest($lists));
            } catch (\Throwable $e) {
                throw core\Error::EApi([
                    'message' => 'Failed to fetch client manifest',
                    'previous' => $e
                ]);
            }

            $cache->setSession('client:'.$this->_id, $manifest);
        }

        return $manifest;
    }


    public function updateListUserDetails(string $oldEmail, user\IClientDataObject $client)
    {
        try {
            $this->_adapter->updateListUserDetails($oldEmail, $client, $this->getManifest());
        } catch (\Throwable $e) {
            throw core\Error::EApi([
                'message' => 'Unable to update list user details',
                'previous' => $e
            ]);
        }

        return $this;
    }


    public function unsubscribeUserFromList(user\IClientDataObject $client, string $listId)
    {
        try {
            $this->_adapter->unsubscribeUserFromList($client, $listId);
        } catch (\Throwable $e) {
            throw core\Error::EApi([
                'message' => 'Failed unsubscribing user from list',
                'previous' => $e,
                'data' => [
                    'list' => $listId,
                    'client' => $client
                ]
            ]);
        }

        $cache = flow\mailingList\Cache::getInstance();
        $cache->removeSession('client:'.$this->_id);

        return $this;
    }
}
