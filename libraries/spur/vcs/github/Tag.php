<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\vcs\github;

use df;
use df\core;
use df\spur;

class Tag implements ITag {
    
    use TApiObject;

    protected $_name;
    protected $_version;
    protected $_commit;

    protected function _importData(core\collection\ITree $data) {
        $this->_id = $data->commit['sha'];
        $this->_name = $data['name'];
        $this->_urls['commit'] = $data->commit['url'];
        $this->_commit = new CommitReference($this->_mediator, $data->commit);
    }

    public function getName() {
        return $this->_name;
    }

    public function getVersion() {
        if($this->_version === null) {
            try {
                $this->_version = core\string\Version::factory($this->_name);
            } catch(\Exception $e) {
                $this->_version = false;
            }
        }

        return $this->_version;
    }

    public function getCommit() {
        return $this->_commit;
    }

    public function toArray() {
        return [
            'id' => $this->_id,
            'name' => $this->_name,
            'version' => $this->_version ? (string)$this->_version : null,
            'commit' => $this->_commit->toArray(),
            'urls' => $this->_urls
        ];
    }
}