<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\feed\extension\slash;

use df;
use df\core;
use df\spur;

class EntryReader implements spur\feed\IEntryReaderPlugin {

    use spur\feed\TEntryReader;

    const XPATH_NAMESPACES = [
        'slash10' => 'http://purl.org/rss/1.0/modules/slash/'
    ];

    public function getSection() {
        return $this->_xPath->evaluate(
            'string('.$this->_xPathPrefix.'/slash10:section)'
        );
    }

    public function getDepartment() {
        return $this->_xPath->evaluate(
            'string('.$this->_xPathPrefix.'/slash10:department)'
        );
    }

    public function getHitParade() {
        $paradeString = $this->_xPath->evaluate(
            'string('.$this->_xPathPrefix.'/slash10:hit_parade)'
        );

        $parade = [];

        if($paradeString) {
            foreach(explode(',', $paradeString) as $hit) {
                $parade[] = (int)$hit;
            }
        }

        return $parade;
    }


    public function getCommentCount() {
        return $this->_xPath->evaluate(
            'string('.$this->_xPathPrefix.'/slash10:comments)'
        );
    }
}