<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\vcs\github;

use df;
use df\core;
use df\spur;

class Profile extends User implements IProfile {
    
    protected $_name;
    protected $_email;
    protected $_company;
    protected $_location;
    protected $_isHireable;
    protected $_bio;
    protected $_publicRepos = 0;
    protected $_publicGists = 0;
    protected $_followers = 0;
    protected $_following = 0;
    protected $_creationDate;
    protected $_updateDate;

    protected function _importData(core\collection\ITree $data) {
        parent::_importData($data);

        $this->_name = $data['name'];
        $this->_email = $data['email'];
        $this->_company = $data['company'];
        $this->_location = $data['location'];
        $this->_isHireable = (bool)$data['hireable'];
        $this->_bio = $data['bio'];
        $this->_publicRepos = $data['public_repos'];
        $this->_publicGists = $data['public_gists'];
        $this->_followers = $data['followers'];
        $this->_following = $data['following'];
        $this->_creationDate = core\time\Date::factory($data['created_at']);
        $this->_updateDate = $data['updated_at'] ?
            core\time\Date::factory($data['updated_at']) : clone $this->_creationDate;
    }

    public function getName() {
        return $this->_name;
    }

    public function getEmail() {
        return $this->_email;
    }

    public function getCompany() {
        return $this->_company;
    }

    public function getLocation() {
        return $this->_location;
    }

    public function isHireable() {
        return $this->_isHireable;
    }

    public function getBio() {
        return $this->_bio;
    }

    public function countPublicRepos() {
        return $this->_publicRepos;
    }

    public function countPublicGists() {
        return $this->_publicGists;
    }

    public function countFollowers() {
        return $this->_followers;
    }

    public function countFollowing() {
        return $this->_following;
    }

    public function getCreationDate() {
        return $this->_creationDate;
    }

    public function getUpdateDate() {
        return $this->_updateDate;
    }
}