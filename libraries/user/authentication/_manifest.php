<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\user\authentication;

use df;
use df\core;
use df\user;




// Interfaces
interface IAdapter {
    public function authenticate(IRequest $request, IResult $result);
}


interface IRequest extends core\IAttributeContainer {
    public function setAdapterName($adapter);
    public function getAdapterName();
    
    public function setIdentity($identity);
    public function getIdentity();
    
    public function setCredential($name, $value);
    public function getCredential($name);
}

interface IResult {
    public function setAdapterName($adapter);
    public function getAdapterName();
    
    public function setIdentity($identity);
    public function getIdentity();
    
    public function setCode($code);
    public function getCode();
    public function isValid();
    
    public function setDomainInfo(IDomainInfo $domainInfo);
    public function getDomainInfo();
}

interface IDomainInfo {
    public function getIdentity();
    public function getPassword();
    public function getBindDate();
    
    public function getClientData();
    public function onAuthentication();
}
