<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\user\client;

use df;
use df\core;
use df\axis;
use df\opal;
use df\user;

class Record extends opal\record\Base implements user\IActiveClientDataObject {
    
    const BROADCAST_HOOK_EVENTS = true;

    use user\TNameExtractor;

    protected function _onPreUpdate($taskSet, $task) {
        $unit = $this->getRecordAdapter();

        if(!$this['nickName']) {
            $parts = explode(' ', $this['fullName'], 2);
            $this['nickName'] = array_shift($parts);
        }

        if($this['timezone'] == 'UTC') {
            $this['timezone'] = $unit->context->i18n->timezones->suggestForCountry($this['country']);
        }

        if($this->hasChanged('email')) {
            $localTask = $taskSet->addRawQuery('updateLocalAdapter', 
                $unit->getModel()->auth->update([
                        'identity' => $this['email']
                    ])
                    ->where('user', '=', $this['id'])
                    ->where('adapter', '=', 'Local')
            );

            $localTask->addDependency($task);
        }

        $regenTask = $taskSet->addGenericTask($unit, 'regenKeyring', function($task, $transaction) {
            $task->getAdapter()->context->user->refreshClientData();
            $task->getAdapter()->context->user->instigateGlobalKeyringRegeneration();
        });

        $regenTask->addDependency($task);
    }

    protected function _onPreDelete($taskSet, $task) {
        $id = $this['id'];
        $unit = $this->getRecordAdapter();

        $localTask = $taskSet->addRawQuery('deleteAuths',
            $unit->getModel()->auth->delete()
                ->where('user', '=', $id)
        );

        $localTask->addDependency($task);
    }

    public function getId() {
        return $this['id'];
    }
    
    public function getEmail() {
        return $this['email'];
    }
    
    public function getFullName() {
        return $this['fullName'];
    }
    
    public function getNickName() {
        return $this['nickName'];
    }
    
    public function getStatus() {
        return $this['status'];
    }
    
    public function getJoinDate() {
        return $this['joinDate'];
    }
    
    public function getLoginDate() {
        return $this['loginDate'];
    }
    
    public function getLanguage() {
        return $this['language'];
    }
    
    public function getCountry() {
        return $this['country'];
    }
    
    public function getTimezone() {
        return $this['timezone'];
    }

    public function getGroupIds() {
        return $this->groups->getRelatedPrimaryKeys();
    }
    
    
    public function onAuthentication() {
        $this->loginDate = 'now';
        $this->save();
    }

    public function hasLocalAuth() {
        return (bool)$this->authDomains->select()
            ->where('adapter', '=', 'Local')
            ->count();
    }


    public function setAsDeactivated() {
        $this['status'] = user\IState::DEACTIVATED;
        return $this;
    }

    public function setAsPending() {
        $this['status'] = user\IState::PENDING;
        return $this;
    }

    public function setAsConfirmed() {
        $this['status'] = user\IState::CONFIRMED;
        return $this;
    }
}
