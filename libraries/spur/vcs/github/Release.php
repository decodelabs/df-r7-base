<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\vcs\github;

use df;
use df\core;
use df\spur;

class Release implements IRelease {

    use TApiObject;

    protected $_name;
    protected $_tagName;
    protected $_author;
    protected $_creationDate;
    protected $_publishDate;
    protected $_isDraft;
    protected $_isPrerelease;
    
    protected function _importData(core\collection\ITree $data) {
        $this->_name = $data['name'];
        $this->_tagName = $data['tag_name'];
        $this->_author = new User($this->_mediator, $data->author);
        $this->_creationDate = core\time\Date::factory($data['created_at']);
        $this->_publishDate = $data['published_at'] ?
            core\time\Date::factory($data['published_at']) : null;
        $this->_isDraft = (bool)$data['draft'];
        $this->_isPrerelease = (bool)$data['prerelease'];
    }

    public function getName() {
        return $this->_name;
    }

    public function getTagName() {
        return $this->_tagName;
    }

    public function getCreationDate() {
        return $this->_creationDate;
    }

    public function getPublishDate() {
        return $this->_publishDate;
    }

    public function isDraft() {
        return $this->_isDraft;
    }

    public function isPrerelease() {
        return $this->_isPrerelease;
    }
}