<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\vcs\github;

use df;
use df\core;
use df\spur;

class CommitReference implements ICommitReference, core\IDumpable {

    use TApiObject;

    protected function _importData(core\collection\ITree $data) {
        $this->_id = $data['sha'];
    }

    public function getSha() {
        return $this->_id;
    }

    public function toArray(): array {
        return [
            'sha' => $this->_id,
            'urls' => $this->_urls
        ];
    }


// Dump
    public function getDumpProperties() {
        return array_merge(['sha' => $this->_id], $this->_urls);
    }
}