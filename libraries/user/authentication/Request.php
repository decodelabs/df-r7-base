<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\user\authentication;

use df\core;

class Request implements IRequest
{
    use core\collection\TAttributeContainer;

    protected $_adapterName;
    protected $_identity;
    protected $_credentials = [];

    public function __construct($adapterName = null)
    {
        $this->setAdapterName($adapterName);
    }

    public function setAdapterName($adapter)
    {
        $this->_adapterName = ucfirst($adapter);
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


    public function setCredential($name, $value)
    {
        $this->_credentials[strtolower((string)$name)] = $value;
        return $this;
    }

    public function getCredential($name)
    {
        $name = strtolower((string)$name);

        if (isset($this->_credentials[$name])) {
            return $this->_credentials[$name];
        }
    }
}
