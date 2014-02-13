<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\user\authentication\adapter;

use df;
use df\core;
use df\user;
use df\axis;
use df\opal;

class Local implements user\authentication\IAdapter {
    
    use user\authentication\TAdapter;

    public static function getDefaultConfigValues() {
        return [
            'enabled' => true
        ];
    }
    
    public function authenticate(user\authentication\IRequest $request, user\authentication\IResult $result) {
        $application = $this->_manager->getApplication();
        $model = $this->_manager->getUserModel();
        $domainInfo = $model->getAuthenticationDomainInfo($request);
        
        if(!$domainInfo instanceof user\authentication\IDomainInfo) {
            $result->setCode(user\authentication\Result::IDENTITY_NOT_FOUND);
            return $result;
        }
        
        $result->setDomainInfo($domainInfo);
            
        $passwordHash = core\string\Util::passwordHash(
            $request->getCredential('password'),
            $application->getPassKey()
        );
        
        $domainPassword = $domainInfo->getPassword();
            
        if($domainPassword != $passwordHash) {
            $result->setCode(user\authentication\Result::INVALID_CREDENTIAL);
            return;
        }
        
        $result->setCode(user\authentication\Result::SUCCESS);
        return $this;
    }
}
