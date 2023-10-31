<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\models\user\client\procedures;

use DecodeLabs\Disciple;
use DecodeLabs\Exceptional;
use DecodeLabs\R7\Config\Users as UserConfig;
use DecodeLabs\R7\Legacy;
use df\axis;

class Update extends axis\procedure\Record
{
    protected function _getRecord()
    {
        if (!Disciple::isLoggedIn()) {
            throw Exceptional::Unauthorized(
                'Cannot edit guests'
            );
        }

        $record = $this->_unit->fetch()
            ->where('id', '=', Disciple::getId())
            ->toRow();

        if (!$record) {
            throw Exceptional::{'df/opal/record/NotFound'}(
                'Client record not found'
            );
        }

        return $record;
    }

    protected function _execute()
    {
        $auth = $this->record->authDomains->fetch()
            ->where('adapter', '=', 'Local')
            ->toRow();

        $applyPassword = false;

        if (!empty($this->values[$this->validator->getMappedName('newPassword')])) {
            if (!$auth) {
                $auth = $this->_model->auth->newRecord([
                    'user' => $this->record,
                    'adapter' => 'Local',
                    'bindDate' => 'now'
                ]);
            }

            $userConfig = UserConfig::load();
            $applyPassword = true;

            $this->validator

                // Current
                ->chainIf(!$auth->isNew() && isset($this->values->{$this->validator->getMappedName('currentPassword')}), function ($validator) use ($auth) {
                    $validator->addField('currentPassword', 'text')
                        ->isRequired(true)
                        ->extend(function ($value, $field) use ($auth) {
                            $hash = Legacy::hash($value);

                            if ($hash != $auth['password']) {
                                $field->addError('incorrect', $this->context->_(
                                    'This password is incorrect'
                                ));
                            }
                        });
                })

                // New password
                ->addField('newPassword', 'password')
                    ->isRequired(true)
                    ->setMatchField('confirmNewPassword')
                    ->shouldCheckStrength($userConfig->shouldCheckPasswordStrength())
                    ->setMinStrength($userConfig->getMinPasswordStrength());
        }

        if ($this->validate()) {
            $this->validator->applyTo($this->record, [
                'email', 'fullName', 'nickName',
                'timezone', 'country', 'language'
            ]);

            $this->record->save();

            if ($auth) {
                if ($applyPassword) {
                    $auth->password = $this->validator['newPassword'];
                }

                $auth->identity = $this->record['email'];
                $auth->save();
            }

            if ($this->record['id'] == Disciple::getId()) {
                $this->user->importClientData($this->record);
            }
        }
    }
}
