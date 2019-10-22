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
interface IAdapter
{
    public static function getDefaultConfigValues();
    public static function getDisplayName(): string;
    public function getName(): string;
    public function authenticate(IRequest $request, Result $result);
}

trait TAdapter
{
    protected $_manager;

    public function __construct(user\IManager $manager)
    {
        $this->_manager = $manager;
    }

    public function getName(): string
    {
        $parts = explode('\\', get_class($this));
        return array_pop($parts);
    }
}

interface IIdentityRecallAdapter extends IAdapter
{
    public function recallIdentity();
}


interface IRequest extends core\collection\IAttributeContainer
{
    public function setAdapterName($adapter);
    public function getAdapterName();

    public function setIdentity($identity);
    public function getIdentity();

    public function setCredential($name, $value);
    public function getCredential($name);
}

interface IDomainInfo
{
    public function getIdentity();
    public function getPassword();
    public function getBindDate();

    public function getClientData();
    public function onAuthentication(bool $asAdmin=false);
}
