<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\navigation;

use df;
use df\core;
use df\arch;

class SitemapEntry implements ISitemapEntry {

    protected $_url;
    protected $_lastModified;
    protected $_changeFrequency;
    protected $_priority;

    public function __construct(string $url, $lastModified=null, $changeFrequency=null, $priority=null) {
        $this->setUrl($url);
        $this->setLastModifiedDate($lastModified);
        $this->setChangeFrequency($changeFrequency);
        $this->setPriority($priority);
    }

    public function setUrl(string $url) {
        $this->_url = $url;
        return $this;
    }

    public function getUrl() {
        return $this->_url;
    }

    public function setLastModifiedDate($date) {
        if($date !== null) {
            $date = core\time\Date::factory($date);
        }

        $this->_lastModified = $date;
        return $this;
    }

    public function getLastModifiedDate() {
        return $this->_lastModified;
    }

    public function setChangeFrequency($frequency) {
        switch($frequency) {
            case 'always':
            case 'hourly':
            case 'daily':
            case 'weekly':
            case 'monthly':
            case 'yearly':
            case 'never':
                break;

            default: $frequency = null;
        }

        $this->_changeFrequency = $frequency;
        return $this;
    }

    public function getChangeFrequency() {
        return $this->_changeFrequency;
    }

    public function setPriority($priority) {
        if($priority !== null) {
            $priority = min(abs($priority), 1);
        }

        $this->_priority = $priority;
        return $this;
    }

    public function getPriority() {
        return $this->_priority;
    }
}