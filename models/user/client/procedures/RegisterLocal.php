<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\models\user\client\procedures;

use DecodeLabs\Disciple;
use DecodeLabs\Exceptional;
use DecodeLabs\R7\Config\Users as UserConfig;
use df\axis;
use df\opal;

class RegisterLocal extends axis\procedure\Record
{
    public const CAN_CREATE = true;

    protected function _prepare()
    {
        if (Disciple::isLoggedIn()) {
            throw Exceptional::Unauthorized(
                'Already logged in'
            );
        }
    }

    protected function _execute($invite = null)
    {
        $userConfig = UserConfig::load();

        $this->validator

            // New password
            ->addField('password')
                ->isRequired(true)
                ->setMatchField('confirmPassword')
                ->shouldCheckStrength($userConfig->shouldCheckPasswordStrength())
                ->setMinStrength($userConfig->getMinPasswordStrength());

        $this->validate();

        if ($this->isValid()) {
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

            if (is_int($invite)) {
                $invite = $this->_model->invite->fetch()
                    ->where('id', '=', $invite)
                    ->toRow();
            } elseif (is_string($invite)) {
                $invite = $this->_model->invite->fetch()
                    ->where('key', '=', $invite)
                    ->toRow();
            } elseif (!$invite instanceof opal\record\IRecord) {
                $invite = null;
            }

            if ($invite) {
                $this->record->groups->addList($invite['#groups']);
            }

            $this->record->save();
            $auth->save();

            if ($invite) {
                $this->_model->invite->claim($invite, $this->record);
            }

            if ($userConfig->shouldLoginOnRegistration()) {
                $this->user->auth->bind(
                    $this->user->auth->newRequest('Local')
                        ->setIdentity($auth['identity'])
                        ->setCredential('password', $this->values['password'])
                    //->setAttribute('rememberMe', (bool)true)
                );
            }
        }
    }
}
