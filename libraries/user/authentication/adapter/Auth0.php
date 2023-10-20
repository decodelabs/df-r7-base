<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\user\authentication\adapter;

use Auth0\SDK\Auth0 as Auth0Conn;
use DecodeLabs\Exceptional;
use DecodeLabs\R7\Config\Authentication as AuthConfig;
use DecodeLabs\R7\Legacy;
use df\core;
use df\user;

class Auth0 implements user\authentication\IAdapter
{
    use user\authentication\TAdapter;

    public static function getDefaultConfigValues()
    {
        return [
            'enabled' => false,
            'domain' => null,
            'clientId' => null,
            'clientSecret' => null,
            'autoRegister' => [
                'enabled' => true,
                'groups' => []
            ]
        ];
    }

    public static function getDisplayName(): string
    {
        return 'Auth0';
    }

    public static function newConnection(string $redirect = null)
    {
        $config = AuthConfig::load();
        $options = $config->getOptionsFor('Auth0');

        if (null === ($domain = $options['domain'])) {
            throw Exceptional::Setup([
                'message' => 'Auth0 connection domain has not been specified'
            ]);
        }

        $context = self::_getContext();
        $redirect = $context->uri($redirect ?? 'account/auth0/login');

        return new Auth0Conn([
            'domain' => $domain,
            'client_id' => $options['clientId'],
            'client_secret' => $options['clientSecret'],
            'redirect_uri' => (string)$redirect,
            'audience' => 'https://' . $domain . '/userinfo',
            'scope' => 'openid profile email zoneinfo',
            'store' => false,
            'state_handler' => false
        ]);
    }

    protected static function _getContext()
    {
        return Legacy::getContext();
    }

    public function authenticate(user\authentication\IRequest $request, user\authentication\Result $result)
    {
        $connection = $this->newConnection(
            $request->getAttribute('redirect')
        );

        try {
            $userInfo = $connection->getUser();
        } catch (\Throwable $e) {
            $result->setCode(user\authentication\Result::FAILURE);
            return $this;
        }

        if (!$userInfo) {
            $connection->login();
        }

        $activeId = $request->getIdentity();
        $request->setIdentity($userInfo['sub']);

        $model = $this->_manager->getUserModel();
        $domainInfo = $model->getAuthenticationDomainInfo($request);


        if (!$domainInfo instanceof user\authentication\IDomainInfo) {
            $client = $model->client->fetch()
                ->chainIf($activeId !== null, function ($query) use ($activeId) {
                    $query->where('id', '=', $activeId);
                }, function ($query) use ($userInfo) {
                    $query->where('email', '=', $userInfo['email']);
                })
                ->toRow();

            if (!$client) {
                if ($activeId) {
                    $result->setCode(user\authentication\Result::IDENTITY_NOT_FOUND);
                    return $this;
                }

                $config = AuthConfig::load();
                $options = $config->getOptionsFor('Auth0');

                if (!$options->autoRegister['enabled']) {
                    $result->setCode(user\authentication\Result::IDENTITY_NOT_FOUND);
                    return $this;
                }

                $locale = new core\i18n\Locale($userInfo['locale']);

                if (isset($userInfo['zoneinfo'])) {
                    $timezone = $userInfo['zoneinfo'];
                } else {
                    $timezone = core\i18n\Manager::getInstance()->timezones->suggestForCountry(
                        $locale->getRegion()
                    );
                }

                $client = $model->client->newRecord([
                    'email' => $userInfo['email'],
                    'fullName' => $userInfo['name'],
                    'nickName' => $userInfo['nickname'],
                    'joinDate' => 'now',
                    'status' => 3,
                    'timezone' => $timezone,
                    'country' => $locale->getRegion(),
                    'language' => $locale->getLanguage(),
                    'groups' => $model->group->select('id')
                        ->where('signifier', 'in', $options->autoRegister->groups->toArray())
                        ->toList('id')
                ])->save();
            }

            $domainInfo = $model->auth->newRecord([
                'user' => $client['id'],
                'adapter' => 'Auth0',
                'identity' => $userInfo['sub'],
                'bindDate' => 'now'
            ])->save();
        }

        $result->setDomainInfo($domainInfo);
        return $this;
    }
}
