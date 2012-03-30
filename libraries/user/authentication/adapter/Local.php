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
    
    protected $_manager;
    
    public function __construct(user\IManager $manager) {
        $this->_manager = $manager;
    }
    
    public function authenticate(user\authentication\IRequest $request, user\authentication\IResult $result) {
        $application = $this->_manager->getApplication();
            
        $passwordHash = core\string\Util::passwordHash(
            $request->getCredential('password'),
            $application->getPassKey()
        );
        
        $domainInfo = $result->getDomainInfo();
        $domainPassword = $domainInfo->getPassword();
            
        if($domainPassword != $passwordHash) {
            $result->setCode(user\authentication\Result::INVALID_CREDENTIAL);
            return;
        }
        
        $result->setCode(user\authentication\Result::SUCCESS);
        return $this;
    }
}
