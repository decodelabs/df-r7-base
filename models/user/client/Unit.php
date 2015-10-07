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
use df\flex;

class Unit extends axis\unit\table\Base {

    const NAME_FIELD = 'fullName';

    protected $_defaultSearchFields = [
        'fullName' => 5,
        'nickName' => 3,
        'email' => 1,
        'id' => 10
    ];

    protected $_defaultOrderableFields = [
        'email', 'fullName', 'nickName', 'status', 'joinDate',
        'loginDate', 'timezone', 'country', 'language'
    ];

    protected $_defaultOrder = 'fullName';

    protected function createSchema($schema) {
        $schema->addField('id', 'AutoId', 8);
        $schema->addUniqueField('email', 'Text', 255);
        $schema->addField('fullName', 'Text', 255);
        $schema->addField('nickName', 'Text', 128)
            ->isNullable(true);

        $schema->addField('joinDate', 'Date');
        $schema->addIndexedField('loginDate', 'Date:Time')
            ->isNullable(true);

        $schema->addField('groups', 'ManyToMany', 'group', 'users')
            ->isDominant(true);

        $schema->addField('authDomains', 'OneToMany', 'auth', 'user');
        $schema->addField('options', 'OneToMany', 'option', 'user');
        $schema->addIndexedField('status', 'Number', 1)
            ->setDefaultValue(3);

        $schema->addField('timezone', 'Text', 32)
            ->setDefaultValue('UTC');
        $schema->addField('country', 'Text', 2, flex\ICase::UPPER)
            ->setDefaultValue('GB');
        $schema->addField('language', 'Text', 2, flex\ICase::LOWER)
            ->setDefaultValue('en');
    }


    public function emailExists($email) {
        $output = $this->select('id')->where('email', '=', $email)->toValue('id');

        if($output === null) {
            $output = false;
        }

        return $output;
    }

    public function fetchByEmail($email) {
        return $this->fetch()->where('email', '=', $email)->toRow();
    }

    public function fetchActive() {
        if(!$this->context->user->isLoggedIn()) {
            return null;
        }

        return $this->fetch()
            ->where('id', '=', $this->context->user->client->getId())
            ->toRow();
    }

    public function fetchDetailsForMail($id) {
        $output = $this->select('id', 'fullName as name', 'email')
            ->where('id', '=', $id)
            ->toRow();

        if($output) {
            $parts = explode(' ', $output['name'], 2);
            $output['firstName'] = array_shift($parts);
            $output['surname'] = array_shift($parts);
        }

        return $output;
    }



// Actions
    public function prepareValidator(core\validate\IHandler $validator, opal\record\IRecord $record=null) {
        $isNew = !$record || $record->isNew();

        $validator

            // Email
            ->addRequiredField('email')
                ->isOptional(!$isNew)
                ->setRecord($record)
                ->setUniqueErrorMessage($this->context->_('This email address is already in use by another account'))

            // Full name
            ->addRequiredField('fullName', 'text')
                ->isOptional(!$isNew)

            // Nick name
            ->addRequiredField('nickName', 'text')
                ->isOptional(true)
                ->setSanitizer(function($value, $field) {
                    if(empty($value)) {
                        $parts = explode(' ', $field->validator['fullName']);
                        $value = array_shift($parts);
                    }

                    return $value;
                })

            // Status
            ->addRequiredField('status', 'integer')
                ->isOptional(true)
                ->setCustomValidator(function($node, $value) {
                    if($value < -1 || $value > 3) {
                        $node->addError('invalid', $this->context->_(
                            'Please enter a valid status id'
                        ));
                    }
                })

            // Timezone
            ->addRequiredField('timezone', 'text')
                ->isOptional(true)
                ->setSanitizer(function($value) {
                    return str_replace(' ', '/', ucwords(str_replace('/', ' ', $value)));
                })
                ->setCustomValidator(function($node, $value) {
                    if(!$this->context->i18n->timezones->isValidId($value)) {
                        $node->addError('invalid', $this->context->_(
                            'Please enter a valid timezone id'
                        ));
                    }
                })

            // Country
            ->addRequiredField('country', 'text')
                ->isOptional(true)
                ->setSanitizer(function($value) {
                    return strtoupper($value);
                })
                ->setCustomValidator(function($node, $value) {
                    if(!$this->context->i18n->countries->isValidId($value)) {
                        $node->addError('invalid', $this->context->_(
                            'Please enter a valid country code'
                        ));
                    }
                })

            // Language
            ->addRequiredField('language', 'text')
                ->isOptional(true)
                ->setSanitizer(function($value) {
                    return strtolower($value);
                })
                ->setCustomValidator(function($node, $value) {
                    if(!$this->context->i18n->languages->isValidId($value)) {
                        $node->addError('invalid', $this->context->_(
                            'Please enter a valid language id'
                        ));
                    }
                });
    }
}
