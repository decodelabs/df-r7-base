<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\vcs\github;

use df;
use df\core;
use df\spur;

class Tag implements ITag, core\IDumpable {
    
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
            $name = $this->_name;

            if(preg_match('/^[a-zA-Z][0-9]/', $name)) {
                $name = substr($name, 1);
            }

            try {
                $this->_version = core\string\Version::factory($name);
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

// Dump
    public function getDumpProperties() {
        return [
            'id' => $this->_id,
            'name' => $this->_name,
            'version' => $this->getVersion(),
            'commit' => $this->_commit,
            'urls' => $this->_urls
        ];
    }
}