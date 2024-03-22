<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\models\user;

use DecodeLabs\Stash;
use df\apex;
use df\axis;
use df\user;

class Model extends axis\Model implements user\IUserModel
{
    public function getClientData($id)
    {
        return $this->client->fetchByPrimary($id);
    }

    public function getClientDataList(array $ids, array $emails = null)
    {
        if (empty($ids) && empty($emails)) {
            return [];
        }

        $query = $this->client->fetch()->where('id', 'in', $ids);

        if ($emails !== null) {
            $query->orWhere('email', 'in', $emails);
        }

        return $query->toArray();
    }

    public function getAuthenticationDomainInfo(user\authentication\IRequest $request)
    {
        return $this->getUnit('auth')->fetch()
            ->where('identity', '=', $request->getIdentity())
            ->where('adapter', '=', $request->getAdapterName())
            ->toRow();
    }

    public function generateKeyring(user\IClient $client)
    {
        if (!$id = $client->getId()) {
            return [];
        }

        $groupBridge = $this->group->getBridgeUnit('roles');
        $clientBridge = $this->client->getBridgeUnit('groups');

        $query = $this->role->select('id')
            ->whereCorrelation('id', 'in', 'role')
                ->from($groupBridge, 'groupBridge')
                ->joinConstraint()
                    ->from($clientBridge, 'clientBridge')
                    ->on('clientBridge.group', '=', 'groupBridge.group')
                    ->endJoin()
                ->where('clientBridge.client', '=', $id)
                ->endCorrelation()
            ->attach('domain', 'pattern', 'allow')
                ->from($this->key, 'key')
                ->on('key.role', '=', 'role.id')
                ->asMany('keys')
            ->orderBy('priority ASC');

        $output = [];

        foreach ($query as $role) {
            foreach ($role['keys'] as $key) {
                if (!isset($output[$key['domain']])) {
                    $output[$key['domain']] = [];
                }

                $output[$key['domain']][$key['pattern']] = (bool)$key['allow'];
            }
        }

        return $output;
    }

    public function fetchClientOptions($id)
    {
        return $this->option->select('key', 'data')
            ->where('user', '=', $id)
            ->toList('key', 'data');
    }

    public function updateClientOptions($id, array $options)
    {
        $q = $this->option->batchReplace();

        foreach ($options as $key => $value) {
            $q->addRow([
                'user' => $id,
                'key' => $key,
                'data' => $value
            ]);
        }

        $q->execute();
        return $this;
    }

    public function removeClientOptions($id, $keys)
    {
        $this->option->delete()
            ->where('user', '=', $id)
            ->where('key', 'in', $keys)
            ->execute();

        return $this;
    }


    public function canUserAccess($user, $lock)
    {
        if (!$user instanceof user\IClient) {
            if (!$user instanceof apex\models\user\client\Record) {
                $user = $this->getClientData($user);

                if (!$user) {
                    return false;
                }
            }

            $user = user\Client::factory($user);
        }

        if (!$user->getKeyringTimestamp()) {
            $user->setKeyring($this->generateKeyring($user));
        }

        if (!$lock instanceof user\IAccessLock) {
            $lock = $this->context->user->getAccessLock($lock);
        }

        return $user->canAccess($lock);
    }

    public function isUserA($user, string ...$signifiers): bool
    {
        return (bool)$this->group->select()
            ->where('users', '=', $user)
            ->beginWhereClause()
                ->where('signifier', 'in', $signifiers)
                ->orWhereCorrelation('id', 'in', 'group')
                    ->from($this->group->getBridgeUnit('roles'), 'bridge')
                    ->whereCorrelation('role', 'in', 'id')
                        ->from('axis://user/Role', 'role')
                        ->where('role.signifier', 'in', $signifiers)
                        ->endCorrelation()
                    ->endCorrelation()
                ->endClause()
            ->count();
    }


    public function installDefaultManifest()
    {
        $roleIds = $this->role->select('id', 'name')->toList('id', 'name');
        $groupIds = $this->group->select('id', 'name')->toList('id', 'name');

        foreach ($this->role->getDefaultManifest() as $id => $row) {
            if (isset($roleIds[$id])) {
                continue;
            }

            $row['id'] = $id;
            $keys = $row['keys'];
            unset($row['keys']);

            $role = $this->role->newRecord($row);

            foreach ($keys as $key) {
                $role->keys->add($this->key->newRecord($key));
            }

            $role->save();
        }

        foreach ($this->group->getDefaultManifest() as $id => $row) {
            if (isset($groupIds[$id])) {
                continue;
            }

            $row['id'] = $id;
            $group = $this->group->newRecord($row);
            $group->save();
        }

        return $this;
    }


    public function setAvatarCacheTime(): int
    {
        $cache = Stash::load('model.user');
        $cache->set('avatarCacheTime', $time = time());
        return $time;
    }

    public function getAvatarCacheTime(): int
    {
        $cache = Stash::load('model.user');
        $time = $cache->get('avatarCacheTime');

        if (!$time) {
            $time = $this->setAvatarCacheTime();
        }

        return $time;
    }
}
