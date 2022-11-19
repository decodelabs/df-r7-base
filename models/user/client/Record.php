<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\user\client;

use DateTime;
use df\opal;

use df\user;

class Record extends opal\record\Base implements user\IActiveClientDataObject
{
    public const BROADCAST_HOOK_EVENTS = true;

    use user\TNameExtractor;

    protected $_groupsChanged = false;

    protected function onPreSave($queue, $job)
    {
        if (!$this['nickName']) {
            $this['nickName'] = $this->getFirstName();
        }

        if ($this['timezone'] == 'UTC') {
            $this['timezone'] = $this->getAdapter()->context->i18n->timezones->suggestForCountry($this['country']);
        }
    }

    protected function onPreUpdate($queue, $job)
    {
        $unit = $this->getAdapter();

        if ($this->hasChanged('email')) {
            $queue->after(
                $job,
                'updateLocalAdapter',
                $unit->getModel()->auth->update([
                        'identity' => $this['email']
                    ])
                    ->where('user', '=', $this['id'])
                    ->where('adapter', '=', 'Local')
            );
        }

        $this->_groupsChanged = $this->hasChanged('groups');

        if ($this->hasChanged('status') && $this['status'] == user\IState::DEACTIVATED) {
            $queue->emitEventAfter($job, $this, 'deactivate');
        }
    }

    protected function onUpdate($queue, $job)
    {
        if ($this->_groupsChanged) {
            $regenJob = $queue->after($job, 'regenKeyring', function () {
                $this->getAdapter()->context->user->refreshClientData();
                $this->getAdapter()->context->user->instigateGlobalKeyringRegeneration();
            });

            $this->_groupsChanged = false;
        }
    }

    protected function onPreDelete($queue, $job)
    {
        $id = $this['id'];
        $unit = $this->getAdapter();

        $queue->after(
            $job,
            'deleteAuths',
            $unit->getModel()->auth->delete()
                ->where('user', '=', $id)
        );
    }

    public function getId(): ?string
    {
        return $this['id'];
    }

    public function getEmail(): ?string
    {
        return $this['email'];
    }

    public function getFullName(): ?string
    {
        return $this['fullName'];
    }

    public function getNickName(): ?string
    {
        return $this['nickName'];
    }

    public function getStatus()
    {
        return $this['status'];
    }

    public function getRegistrationDate(): ?DateTime
    {
        if (!$date = $this['joinDate']) {
            return null;
        }

        return $date->getRaw();
    }

    public function getLastLoginDate(): ?DateTime
    {
        if (!$date = $this['loginDate']) {
            return null;
        }

        return $date->getRaw();
    }

    public function getLanguage(): ?string
    {
        return $this['language'];
    }

    public function getCountry(): ?string
    {
        return $this['country'];
    }

    public function getTimezone(): ?string
    {
        if ($this['timezone'] === 'Asia/Yangon') {
            $this['timezone'] = 'Asia/Rangoon';
        }

        return $this['timezone'];
    }

    public function getGroupIds()
    {
        return $this['#groups'];
    }

    public function getSignifiers(): array
    {
        if (!$id = $this['id']) {
            return [];
        }

        $model = $this->getAdapter()->getModel();
        $groupBridge = $model->group->getBridgeUnit('roles');
        $clientBridge = $model->client->getBridgeUnit('groups');

        $roleSigs = $model->role->selectDistinct('signifier')
            ->whereCorrelation('id', 'in', 'role')
                ->from($groupBridge, 'groupBridge')
                ->joinConstraint()
                    ->from($clientBridge, 'clientBridge')
                    ->on('clientBridge.group', '=', 'groupBridge.group')
                    ->endJoin()
                ->where('clientBridge.client', '=', $id)
                ->endCorrelation()
            ->where('signifier', '!=', null)
            ->toList('signifier');

        $groupSigs = $this->groups->selectDistinct('signifier')
            ->where('signifier', '!=', null)
            ->toList('signifier');

        return array_unique(array_merge($roleSigs, $groupSigs));
    }


    public function onAuthentication(user\IClient $client, bool $asAdmin = false)
    {
        if ($asAdmin) {
            return;
        }

        $this->loginDate = 'now';
        $this->country = $client->getCountry();
        $this->timezone = $client->getTimezone();
        $this->save();
    }

    public function hasLocalAuth()
    {
        return (bool)$this->authDomains->select()
            ->where('adapter', '=', 'Local')
            ->count();
    }


    public function setAsDeactivated()
    {
        $this['status'] = user\IState::DEACTIVATED;
        return $this;
    }

    public function setAsPending()
    {
        $this['status'] = user\IState::PENDING;
        return $this;
    }

    public function setAsConfirmed()
    {
        $this['status'] = user\IState::CONFIRMED;
        return $this;
    }
}
