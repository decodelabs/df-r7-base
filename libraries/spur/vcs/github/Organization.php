<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\vcs\github;

use df\core;

class Organization implements IOrganization
{
    use TApiObject;

    protected $_name;
    protected $_description;

    protected function _importData(core\collection\ITree $data)
    {
        $this->_name = $data['login'];
        $this->_description = $data['description'];
    }

    public function getName(): string
    {
        return $this->_name;
    }

    public function getDescription()
    {
        return $this->_description;
    }


    // Ext
    public function getRepositories()
    {
        return $this->_mediator->getOrganizationRepositories($this->_name);
    }
}
