<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\user\authentication;

use df;
use df\core;
use df\user;

class Result
{
    const SUCCESS = 1;
    const FAILURE = 0;
    const IDENTITY_NOT_FOUND = -1;
    const INVALID_CREDENTIAL = -2;
    const ERROR = -3;
    const NO_STATUS = -4;

    protected $_adapterName;
    protected $_identity;
    protected $_code = self::SUCCESS;
    protected $_domainInfo;


    public function __construct($adapterName=null)
    {
        $this->setAdapterName($adapterName);
    }

    public function setAdapterName($adapter)
    {
        $this->_adapterName = $adapter;
        return $this;
    }

    public function getAdapterName()
    {
        return $this->_adapterName;
    }


    public function setIdentity($identity)
    {
        $this->_identity = $identity;
        return $this;
    }

    public function getIdentity()
    {
        return $this->_identity;
    }

    public function setCode($code)
    {
        switch ((int)$code) {
            case self::SUCCESS:
            case self::FAILURE:
            case self::IDENTITY_NOT_FOUND:
            case self::INVALID_CREDENTIAL:
            case self::ERROR:
            case self::NO_STATUS:
                $this->_code = $code;
                break;

            default:
                $this->_code = self::FAILURE;
                break;
        }

        return $this;
    }

    public function getCode()
    {
        return $this->_code;
    }

    public function isValid(): bool
    {
        return $this->_code > 0;
    }

    public function setDomainInfo(IDomainInfo $domainInfo)
    {
        $this->_domainInfo = $domainInfo;
        return $this;
    }

    public function getDomainInfo()
    {
        return $this->_domainInfo;
    }
}
