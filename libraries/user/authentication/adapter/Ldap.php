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

class Ldap implements user\authentication\IAdapter, user\authentication\IIdentityRecallAdapter {
    
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

    public static function getDisplayName() {
        return 'LDAP Network Domain';
    }

    public function recallIdentity() {
        $application = $this->_manager->getApplication();

        if(!$application instanceof core\application\Http) {
            return null;
        }

        $config = user\authentication\Config::getInstance($application);
        $options = $config->getOptionsFor('Ldap');

        if(!$options->ntlm['enabled'] || !count($options->ntlm->ranges)) {
            return null;
        }

        $httpRequest = $application->getHttpRequest();
        $ip = $httpRequest->getIp();
        $inRange = false;

        foreach($options->ntlm->ranges as $range) {
            if($ip->isInRange($range->getValue())) {
                $inRange = true;
                break;
            }
        }

        if(!$inRange) {
            return null;
        }

        $headers = $httpRequest->getHeaders();

        if(!$headers->has('authorization')) {
            // Is there a cleaner way of doing this?
            header('HTTP/1.1 401 Unauthorized');
            header('WWW-Authenticate: NTLM');
            exit;
        }

        $auth = $headers->get('authorization');

        if(substr($auth, 0, 5) != 'NTLM ') {
            return null;
        }

        $c64 = base64_decode(substr($auth, 5));
        $state = ord($c64{8});

        switch($state) {
            case 1:
                $chars = array(0,2,0,0,0,0,0,0,0,40,0,0,0,1,130,0,0,0,2,2,2,0,0,0,0,0,0,0,0,0,0,0,0);
                $ret = 'NTLMSSP';
                
                foreach($chars as $char) {
                    $ret .= chr($char);
                }

                header('HTTP/1.1 401 Unauthorized');
                header('WWW-Authenticate: NTLM '.trim(base64_encode($ret)));
                exit;
                
            case 3:
                $l = ord($c64{31}) * 256 + ord($c64{30});
                $o = ord($c64{33}) * 256 + ord($c64{32});
                $domain = str_replace("\0", '', substr($c64, $o, $l));

                $l = ord($c64{39}) * 256 + ord($c64{38});
                $o = ord($c64{41}) * 256 + ord($c64{40});
                $user = str_replace("\0", '', substr($c64, $o, $l));

                if(!strlen($user)) {
                    return null;
                }
                
                $request = new user\authentication\Request('ldap');
                $request->setIdentity($user);
                $request->setCredential('domain', $domain);
                $request->setAttribute('ntlm', true);

                return $request;
                
            default:
                return null;
        }

        return null;
    }
    
    public function authenticate(user\authentication\IRequest $request, user\authentication\IResult $result) {
        $application = $this->_manager->getApplication();
        
        if($request->getAttribute('ntlm')) {
            $isNtlm = true;

            $identity = opal\ldap\Identity::factory(
                $request->getIdentity(),
                null,
                $request->getCredential('domain')
            );
        } else {
            $isNtlm = false;

            $identity = opal\ldap\Identity::factory(
                $request->getIdentity(),
                $request->getCredential('password')
            );
        }
        

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
                if($isNtlm) {
                    $adapter->ensureBind();
                } else {
                    $adapter->bind($identity);
                }
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
