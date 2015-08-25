<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\users\_actions;

use df;
use df\core;
use df\apex;
use df\arch;
use df\flow;

class TaskAdd extends arch\task\Action {
    
    protected $_client;
    protected $_auth;

    public function execute() {
        $this->_client = $this->data->newRecord('axis://user/Client', [
            'status' => 3
        ]);

        $this->_auth = $this->data->newRecord('axis://user/Auth', [
            'adapter' => 'Local',
            'bindDate' => 'now'
        ]);

        $locale = $this->i18n->getDefaultLocale();

        $this->_client->email = $this->_askFor('Email address', function($answer) {
            return $this->data->newValidator()
                ->addRequiredField('email')
                    ->setRecord($this->_client)
                    ->setUniqueErrorMessage($this->_('This email address is already in use by another account'));
        }, null, true);

        $this->_auth->identity = $this->_client['email'];

        $this->_auth->password = $this->_askPassword('Password', function($answer) {
            return $this->data->newValidator()
                ->addRequiredField('password');
        });

        $this->_client->fullName = $this->_askFor('Full name', function($answer) {
            return $this->data->newValidator()
                ->addRequiredField('fullName', 'text')
                    ->setMaxLength(255);
        });

        $nickName = $this->format->firstName($this->_client['fullName']);

        $this->_client->nickName = $this->_askFor('Nickname', function($answer) {
            return $this->data->newValidator()
                ->addField('nickName', 'text')
                    ->setMaxLength(128);
        }, $nickName);

        $this->_client->country = $this->_askFor('Country code', function($answer) {
            return $this->data->newValidator()
                ->addRequiredField('country', 'text')
                    ->setSanitizer(function($value) {
                        return strtoupper($value);
                    })
                    ->setCustomValidator(function($node, $value) {
                        if(!$this->i18n->countries->isValidId($value)) {
                            $node->addError('invalid', $this->_(
                                'Please enter a valid country code'
                            ));
                        }
                    });
        }, $locale->getRegion());

        $this->_client->language = $this->_askFor('Language code', function($answer) {
            return $this->data->newValidator()
                ->addRequiredField('language', 'text')
                    ->setSanitizer(function($value) {
                        return strtolower($value);  
                    })
                    ->setCustomValidator(function($node, $value) {
                        if(!$this->i18n->languages->isValidId($value)) {
                            $node->addError('invalid', $this->_(
                                'Please enter a valid language id'
                            ));
                        }
                    });
        }, $locale->getLanguage());

        $this->_client->timezone = $this->_askFor('Timezone', function($answer) {
            return $this->data->newValidator()
                ->addRequiredField('timezone', 'text')
                    ->setSanitizer(function($value) {
                        return str_replace(' ', '/', ucwords(str_replace('/', ' ', $value)));
                    })
                    ->setCustomValidator(function($node, $value) {
                        if(!$this->i18n->timezones->isValidId($value)) {
                            $node->addError('invalid', $this->_(
                                'Please enter a valid timezone id'
                            ));
                        }
                    });
        }, $this->i18n->timezones->suggestForCountry($this->_client['country']));


        $groupIds = ['77abfc6a-bab7-c3fa-f701-e08615a46c35', '8d9bad9e-720e-c643-f701-b0733ea86c35'];
        $groups = $this->data->user->group->fetch()
            ->where('id', 'in', $groupIds)
            ->toKeyArray('id');

        foreach($groupIds as $id) {
            if(!isset($groups[$id])) {
                continue;
            }

            if($this->_askBoolean('Add to '.$groups[$id]['name'].' group?')) {
                $this->_client->groups->add($groups[$id]);
            }
        }

        $this->_client->save();
        $this->_auth->user = $this->_client;
        $this->_auth->save();

        $this->io->writeLine('Done');
    }

    
}