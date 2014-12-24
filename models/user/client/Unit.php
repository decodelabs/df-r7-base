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

class Unit extends axis\unit\table\Base {
    
    protected static $_defaultSearchFields = [
        'fullName' => 5,
        'nickName' => 3,
        'email' => 1,
        'id' => 10
    ];

    protected function _onCreate(axis\schema\ISchema $schema) {
        $schema->addField('id', 'AutoId', 8);
        $schema->addUniqueField('email', 'String', 255);
        $schema->addField('fullName', 'String', 255);
        $schema->addField('nickName', 'String', 128)->isNullable(true);
        $schema->addField('joinDate', 'Date');
        $schema->addIndexedField('loginDate', 'DateTime')->isNullable(true);
        
        $schema->addField('groups', 'ManyToMany', 'group', 'users')
            ->isDominant(true)
            ->setBridgeUnitId('groupBridge')
            ;
            
        $schema->addField('authDomains', 'OneToMany', 'auth', 'user');
        $schema->addField('options', 'OneToMany', 'option', 'user');
        $schema->addIndexedField('status', 'Integer', 1)->setDefaultValue(3);
        
        $schema->addField('timezone', 'String', 32)->setDefaultValue('UTC');
        $schema->addField('country', 'KeyString', 2, core\string\ICase::UPPER)->setDefaultValue('GB');
        $schema->addField('language', 'KeyString', 2, core\string\ICase::LOWER)->setDefaultValue('en');
    }

    public function applyPagination(opal\query\IPaginator $paginator) {
        $paginator
            ->setOrderableFields(
                'email', 'fullName', 'nickName', 'status', 'joinDate',
                'loginDate', 'timezone', 'country', 'language'
            )
            ->setDefaultOrder('fullName');

        return $this;
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
        $validator

            // Email
            ->addRequiredField('email', 'email')
                ->setStorageAdapter($this)
                ->chainIf($record, function($validator) use($record) {
                    $validator->setUniqueFilterId($record['id']);
                })
                ->setUniqueErrorMessage($this->context->_('This email address is already in use by another account'))

            // Full name
            ->addRequiredField('fullName', 'text')

            // Nick name
            ->addRequiredField('nickName', 'text')
                ->setSanitizer(function($value, $field) {
                    if(empty($value)) {
                        $parts = explode(' ', $field->getHandler()['fullName']);
                        $value = array_shift($parts);
                    }

                    return $value;
                })

            // Status
            ->addRequiredField('status', 'integer')
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

    protected function _runUpdateAction($action) {
        if(!$action->record) {
            $userManager = $this->context->user;

            if(!$userManager->isLoggedIn()) {
                $this->context->throwError(403, 'Cannot edit guests');
            }

            $action->record = $this->fetch()
                ->where('id', '=', $userManager->client->getId())
                ->toRow();

            if(!$action->record) {
                $this->context->throwError(500, 'Client record not found');
            }
        }

        $action->prepare();
        $action->validator->removeField('status');

        $auth = $action->record->authDomains->fetch()
            ->where('adapter', '=', 'Local')
            ->toRow();

        $applyPassword = false;

        if(!empty($action->values[$action->validator->getMappedName('newPassword')])) {
            if(!$auth) {
                $auth = $this->_model->auth->newRecord([
                    'user' => $action->record,
                    'adapter' => 'Local',
                    'bindDate' => 'now'
                ]);
            }

            $userConfig = $this->_model->config;
            $applyPassword = true;

            $action->validator

                // Current
                ->chainIf(!$auth->isNew() && isset($action->values->{$action->validator->getMappedName('currentPassword')}), function($validator) use($auth) {
                    $validator->addField('currentPassword', 'text')
                        ->isRequired(true)
                        ->setCustomValidator(function($node, $value, $field) use ($auth) {
                            $hash = $this->context->data->hash($value);

                            if($hash != $auth['password']) {
                                $node->addError('incorrect', $this->context->_('This password is incorrect'));
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


        $action->validate();

        if($action->isValid()) {
            $action->validator->applyTo($action->record, [
                'email', 'fullName', 'nickName', 
                'timezone', 'country', 'language'
            ]);

            $action->record->save();

            if($auth) {
                if($applyPassword) {
                    $auth->password = $action->validator['newPassword'];
                }
                
                $auth->identity = $action->record['email'];
                $auth->save();
            }

            if($action->record['id'] == $this->context->user->client->getId()) {
                $this->context->user->importClientData($action->record);
            }
        }
    }

    protected function _runRegisterLocalAction($action) {
        if($this->context->user->isLoggedIn()) {
            $this->throwError(403, 'Already logged in!');
        }

        $userConfig = $this->_model->config;
        $action->prepare();
        $action->validator->removeField('status');

        $action->validator

            // New password
            ->addField('password', 'password')
                ->isRequired(true)
                ->setMatchField('confirmPassword')
                ->shouldCheckStrength($userConfig->shouldCheckPasswordStrength())
                ->setMinStrength($userConfig->getMinPasswordStrength());

        $action->validate();

        if($action->isValid()) {
            $action->record->joinDate = 'now';

            $action->validator->applyTo($action->record, [
                'email', 'fullName', 'nickName', 
                'timezone', 'country', 'language'
            ]);

            $auth = $this->_model->auth->newRecord([
                'user' => $action->record,
                'adapter' => 'Local',
                'identity' => $action->validator['email'],
                'password' => $action->validator['password'],
                'bindDate' => 'now'
            ]);

            $invite = null;

            if(isset($action->args['inviteKey'])) {
                $invite = $this->_model->invite->fetch()
                    ->where('key', '=', $action->args['inviteKey'])
                    ->toRow();
            } else if(isset($action->args['invite'])) {
                $invite = $action->args['invite'];

                if(!$invite instanceof opal\record\IRecord) {
                    $invite = null;
                }
            }

            if($invite) {
                $client->groups->addList($invite->groups->getRelatedPrimaryKeys());
            }

            $action->record->save();
            $auth->save();

            if($invite) {
                $this->_model->invite->claim($invite, $action->record);
            }

            if($userConfig->shouldLoginOnRegistration()) {
                $request = new user\authentication\Request('Local');
                $request->setIdentity($auth['identity']);
                $request->setCredential('password', $action->values['password']);
                $request->setAttribute('rememberMe', (bool)true);
                $this->context->user->authenticate($request);
            }
        }
    }



// Query blocks
    public function applyLinkRelationQueryBlock(opal\query\IReadQuery $query, $relationField) {
        if($query instanceof opal\query\ISelectQuery) {
            $query->leftJoinRelation($relationField, 'id as '.$relationField.'Id', 'fullName as '.$relationField.'Name')
                ->combine($relationField.'Id as id', $relationField.'Name as fullName')
                    ->nullOn('id')
                    ->asOne($relationField)
                ->paginate()
                    ->addOrderableFields($relationField.'Name')
                    ->end();
        } else {
            $query->populateSelect($relationField, 'id', 'fullName');
        }
    }
}
