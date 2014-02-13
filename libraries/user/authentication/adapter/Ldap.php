<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\user\authentication\adapter;

use df;
use df\core;
use df\user;
use df\opal;

class Ldap implements user\authentication\IAdapter {
    
    use user\authentication\TAdapter;

    public static function getDefaultConfigValues() {
        return [
            'enabled' => false,
            'domains' => [
                'example' => [
                    'type' => 'ActiveDirectory',
                    'host' => 'localhost',
                    'port' => null,
                    'baseDn' => 'dc=example,dc=com',
                    'security' => 'ssl',
                    'privilegedIdentity' => [
                        'username' => 'admin',
                        'password' => 'password'
                    ],
                    'autoRegister' => true
                ]
            ],
            'ntlm' => [
                'enabled' => false,
                'ranges' => [
                    '127.0.0.1'
                ]
            ]
        ];
    }
    
    public function authenticate(user\authentication\IRequest $request, user\authentication\IResult $result) {
        $application = $this->_manager->getApplication();
        
        $identity = opal\ldap\Identity::factory(
            $request->getIdentity(),
            $request->getCredential('password')
        );

        $config = user\authentication\Config::getInstance($application);
        $options = $config->getOptionsFor('Ldap');
        $ldapDomain = $adapter = null;
        $results = [];
        $mainId = null;

        foreach($options->domains as $domainId => $domainOptions) {
            if($domainOptions['enabled'] === false) {
                continue;
            }

            $privilegedIdentity = null;

            if(!$mainId) {
                $mainId = $domainId;
            }

            if($domainOptions->privilegedIdentity->username->hasValue()) {
                $privilegedIdentity = opal\ldap\Identity::factory(
                    $domainOptions->privilegedIdentity['username'],
                    $domainOptions->privilegedIdentity['password']
                );
            }

            $adapter = opal\ldap\Adapter::factory(
                opal\ldap\Connection::factory(
                    $domainOptions['host'],
                    $domainOptions['port'],
                    $domainOptions['security'],
                    $domainOptions['type']
                ),

                opal\ldap\Context::factory($domainOptions['baseDn'])
                    ->setControllerDomain($domainId), 

                $privilegedIdentity
            );

            try {
                $adapter->bind($identity);
            } catch(opal\ldap\BindException $e) {
                switch($e->getCode()) {
                    case opal\ldap\IStatus::SERVER_DOWN:
                    case opal\ldap\IStatus::BUSY:
                    case opal\ldap\IStatus::UNAVAILABLE:
                    case opal\ldap\IStatus::UNWILLING_TO_PERFORM:
                    case opal\ldap\IStatus::TIMEOUT:
                    case opal\ldap\IStatus::CONNECT_ERROR:
                        $results[$domainId] = $result::ERROR;
                        break;
                    
                    case opal\ldap\IStatus::INVALID_CREDENTIALS:
                        $results[$domainId] = $result::INVALID_CREDENTIAL;
                        break;
                        
                    default:
                        $results[$domainId] = $result::FAILURE;
                        break;
                }
            }
            
            if(!$adapter->isBound()) {
                continue;
            }

            $query = $adapter->fetch()->inside(null, true);
            $username = $identity->getUsername();

            if($username instanceof opal\ldap\IRdn) {
                foreach($username->getAttributes() as $key => $value) {
                    $query->where($key, '=', $value);
                }
            } else {
                $query->where('uid', '=', $username);
            }

            if(!$ldapUser = $query->toRow()) {
                $results[$domainId] = $result::IDENTITY_NOT_FOUND;
                continue;
            }

            $ldapDomain = $domainId;
        }

        if(!$ldapDomain || !$ldapUser || !$adapter || !$adapter->isbound()) {
            if($mainId && isset($results[$mainId])) {
                $code = $results[$mainId];
            } else {
                $code = $result::FAILURE;
            }

            return $result->setCode($code);
        }

        if(!$ldapUser['mail']) {
            return $result->setCode($result::FAILURE);
        }

        $globalId = $ldapUser->getGlobalId();

        $model = $this->_manager->getUserModel();
        $domainInfo = $model->getAuthenticationDomainInfo(
            (new user\authentication\Request('Ldap'))
                ->setIdentity($globalId)
        );

        if(!$domainInfo) {
            $client = $model->client->fetch()
                ->where('email', '=', $ldapUser['mail'])
                ->toRow();

            if(!$client) {
                if(!$domainOptions['autoRegister']) {
                    return $result->setCode($result::IDENTITY_NOT_FOUND);
                }

                $name = $ldapUser['displayName'];
                if(empty($name)) $name = $ldapUser['name'];
                if(empty($name)) $name = $ldapUser['cn'];

                $nickName = $ldapUser['givenName'];

                if(empty($nickName)) {
                    $parts = explode(' ', $name);
                    $nickName = array_shift($parts);
                }

                $client = $model->client->newRecord([
                        'email' => $ldapUser['mail'],
                        'fullName' => $name,
                        'nickName' => $nickName,
                        'joinDate' => 'now',
                        'status' => user\IState::BOUND
                    ])
                    ->save();
            }

            $domainInfo = $model->auth->newRecord([
                    'user' => $client,
                    'adapter' => 'Ldap',
                    'identity' => $globalId,
                    'bindDate' => 'now'
                ])
                ->save();
        }

        $result->setDomainInfo($domainInfo);
        $result->setCode($result::SUCCESS);
        return $this;
    }
}
