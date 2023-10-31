<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\users\_nodes;

use DecodeLabs\Dictum;
use DecodeLabs\R7\Legacy;
use DecodeLabs\Terminus as Cli;
use df\arch;

class TaskAdd extends arch\node\Task
{
    protected $_client;
    protected $_auth;

    public function execute(): void
    {
        if (!$this->data->user->client->countAll()) {
            $this->data->user->installDefaultManifest();
        }


        $this->_client = $this->data->newRecord('axis://user/Client', [
            'status' => 3
        ]);

        $this->_auth = $this->data->newRecord('axis://user/Auth', [
            'adapter' => 'Local',
            'bindDate' => 'now'
        ]);

        $locale = $this->i18n->getDefaultLocale();

        $this->_client->email = $this->_askFor('Email address', function ($answer) {
            return $this->data->newValidator()
                ->addRequiredField('email')
                    ->setRecord($this->_client)
                    ->setUniqueErrorMessage($this->_('This email address is already in use by another account'));
        }, null, true);

        $this->_auth->identity = $this->_client['email'];
        $this->_auth->password = Legacy::hash(Cli::askPassword('Password', true, true));

        $this->_client->fullName = $this->_askFor('Full name', function ($answer) {
            return $this->data->newValidator()
                ->addRequiredField('fullName', 'text')
                    ->setMaxLength(255);
        });

        $nickName = Dictum::firstName($this->_client['fullName']);

        $this->_client->nickName = $this->_askFor('Nickname', function ($answer) {
            return $this->data->newValidator()
                ->addField('nickName', 'text')
                    ->setMaxLength(128);
        }, $nickName);

        $this->_client->country = $this->_askFor('Country code', function ($answer) {
            return $this->data->newValidator()
                ->addRequiredField('country', 'text')
                    ->setSanitizer(function ($value) {
                        return strtoupper((string)$value);
                    })
                    ->extend(function ($value, $field) {
                        if (!$this->i18n->countries->isValidId($value)) {
                            $field->addError('invalid', $this->_(
                                'Please enter a valid country code'
                            ));
                        }
                    });
        }, $locale->getRegion());

        $this->_client->language = $this->_askFor('Language code', function ($answer) {
            return $this->data->newValidator()
                ->addRequiredField('language', 'text')
                    ->setSanitizer(function ($value) {
                        return strtolower((string)$value);
                    })
                    ->extend(function ($value, $field) {
                        if (!$this->i18n->languages->isValidId($value)) {
                            $field->addError('invalid', $this->_(
                                'Please enter a valid language id'
                            ));
                        }
                    });
        }, $locale->getLanguage());

        $this->_client->timezone = $this->_askFor('Timezone', function ($answer) {
            return $this->data->newValidator()
                ->addRequiredField('timezone', 'text')
                    ->setSanitizer(function ($value) {
                        return str_replace(' ', '/', ucwords(str_replace('/', ' ', $value)));
                    })
                    ->extend(function ($value, $field) {
                        if (!$this->i18n->timezones->isValidId($value)) {
                            $field->addError('invalid', $this->_(
                                'Please enter a valid timezone id'
                            ));
                        }
                    });
        }, $this->i18n->timezones->suggestForCountry($this->_client['country']));


        $this->_client->save();

        $selectedGroups = isset($this->request['groups']) ?
            $this->request->query->groups->toArray() : null;


        if ($selectedGroups) {
            $groups = $this->data->user->group->fetch()
                ->where('id', 'in', $selectedGroups)
                ->orWhere('signifier', 'in', $selectedGroups)
                ->toArray();

            foreach ($groups as $group) {
                $this->_client->groups->add($group);
            }
        } else {
            $groupIds = ['77abfc6a-bab7-c3fa-f701-e08615a46c35', '8d9bad9e-720e-c643-f701-b0733ea86c35'];

            $groups = $this->data->user->group->fetch()
                ->where('id', 'in', $groupIds)
                ->toKeyArray('id');

            foreach ($groupIds as $id) {
                if (!isset($groups[$id])) {
                    continue;
                }

                if (Cli::confirm('Add to ' . $groups[$id]['name'] . ' group?', true)) {
                    $this->_client->groups->add($groups[$id]);
                }
            }
        }


        $this->_client->save();
        $this->_auth->user = $this->_client;
        $this->_auth->save();

        Cli::success('Done');
    }
}
