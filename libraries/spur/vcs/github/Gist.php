<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\vcs\github;

use df;
use df\core;
use df\spur;

class Gist implements IGist {

    use TApiObject;

    protected $_name;
    protected $_owner;
    protected $_isPublic;
    protected $_creationDate;
    protected $_updateDate;
    protected $_comments = 0;
    protected $_files = [];

    protected function _importData(core\collection\ITree $data) {
        $this->_name = $data['description'];
        $this->_owner = new User($this->_mediator, $data->owner);
        $this->_isPublic = (bool)$data['public'];

        $this->_creationDate = core\time\Date::factory($data['created_at']);
        $this->_updateDate = core\time\Date::factory($data['updated_at'] ?? $this->_creationDate);

        $this->_comments = $data['comments'];

        foreach($data->files as $file) {
            $this->_files = new File($file);
        }
    }

    public function getName() {
        return $this->_name;
    }

    public function getOwner() {
        return $this->_owner;
    }

    public function isPublic() {
        return $this->_isPublic;
    }

    public function getCreationDate() {
        return $this->_creationDate;
    }

    public function getUpdateDate() {
        return $this->_updateDate;
    }

    public function countComments() {
        return $this->_comments;
    }

    public function getFiles() {
        return $this->_files;
    }
}