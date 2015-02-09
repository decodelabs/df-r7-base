<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\user\client\procedures;

use df;
use df\core;
use df\apex;
use df\axis;
use df\user;
use df\opal;

class RegisterLocal extends axis\procedure\Record {
    
    const CAN_CREATE = true;

    protected function _prepare() {
        if($this->context->user->isLoggedIn()) {
            $this->throwError(403, 'Already logged in!');
        }
    }

    protected function _execute($invite=null) {
        $userConfig = $this->_model->config;

        $this->validator

            // New password
            ->addField('password', 'password')
                ->isRequired(true)
                ->setMatchField('confirmPassword')
                ->shouldCheckStrength($userConfig->shouldCheckPasswordStrength())
                ->setMinStrength($userConfig->getMinPasswordStrength());

        $this->validate();

        if($this->isValid()) {
            $this->record->joinDate = 'now';

            $this->validator->applyTo($this->record, [
                'email', 'fullName', 'nickName', 
                'timezone', 'country', 'language'
            ]);

            $auth = $this->_model->auth->newRecord([
                'user' => $this->record,
                'adapter' => 'Local',
                'identity' => $this->validator['email'],
                'password' => $this->validator['password'],
                'bindDate' => 'now'
            ]);

            if(is_int($invite)) {
                $invite = $this->_model->invite->fetch()
                    ->where('id', '=', $invite)
                    ->toRow();
            } else if(is_string($invite)) {
                $invite = $this->_model->invite->fetch()
                    ->where('key', '=', $invite)
                    ->toRow();
            } else if(!$invite instanceof opal\record\IRecord) {
                $invite = null;
            }

            if($invite) {
                $client->groups->addList($invite->groups->getRelatedPrimaryKeys());
            }

            $this->record->save();
            $auth->save();

            if($invite) {
                $this->_model->invite->claim($invite, $this->record);
            }

            if($userConfig->shouldLoginOnRegistration()) {
                $request = new user\authentication\Request('Local');
                $request->setIdentity($auth['identity']);
                $request->setCredential('password', $this->values['password']);
                $request->setAttribute('rememberMe', (bool)true);
                $this->user->authenticate($request);
            }
        }
    }
}