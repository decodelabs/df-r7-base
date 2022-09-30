<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\user\authentication\adapter;

use df;
use df\core;
use df\user;

use DecodeLabs\R7\Legacy;

class Local implements user\authentication\IAdapter
{
    use user\authentication\TAdapter;

    public static function getDefaultConfigValues()
    {
        return [
            'enabled' => true
        ];
    }

    public static function getDisplayName(): string
    {
        return 'Email and password';
    }

    public function authenticate(user\authentication\IRequest $request, user\authentication\Result $result)
    {
        $model = $this->_manager->getUserModel();
        $domainInfo = $model->getAuthenticationDomainInfo($request);

        if (!$domainInfo instanceof user\authentication\IDomainInfo) {
            $result->setCode(user\authentication\Result::IDENTITY_NOT_FOUND);
            return $result;
        }

        $result->setDomainInfo($domainInfo);

        $passwordHash = core\crypt\Util::passwordHash(
            $request->getCredential('password'),
            Legacy::getPassKey()
        );

        $domainPassword = $domainInfo->getPassword();

        if ($domainPassword != $passwordHash) {
            $result->setCode(user\authentication\Result::INVALID_CREDENTIAL);
            return;
        }

        $result->setCode(user\authentication\Result::SUCCESS);
        return $this;
    }
}
