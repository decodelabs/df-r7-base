<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\vcs\github;

use df\core;

class User implements IUser
{
    use TApiObject;

    protected $_username;
    protected $_isSiteAdmin;

    protected function _importData(core\collection\ITree $data)
    {
        $this->_username = $data['login'];
        $this->_isSiteAdmin = (bool)$data['site_admin'];
        $this->_urls['blog'] = $data['blog'];
    }

    public function getUsername()
    {
        return $this->_username;
    }

    public function isSiteAdmin()
    {
        return $this->_isSiteAdmin;
    }

    // Ext
    public function getOrganizations()
    {
        return $this->_mediator->getUserOrganizations($this->_username);
    }

    public function getFollowers()
    {
        return $this->_mediator->getFollowersOf($this->_username);
    }

    public function getFollowing()
    {
        return $this->_mediator->getFollowedBy($this->_username);
    }

    public function getRepositories()
    {
        return $this->_mediator->getUserRepositories($this->_username);
    }

    public function getOwnedRepositories()
    {
        return $this->_mediator->getUserOwnedRepositories($this->_username);
    }

    public function getWatchedRepositories()
    {
        return $this->_mediator->getUserWatchedRespositories($this->_username);
    }

    public function getGists()
    {
        return $this->_mediator->getUserGists($this->_username);
    }

    public function getKeys()
    {
        return $this->_mediator->getUserKeys($this->_username);
    }
}
