<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\feed\extension\content;

use df;
use df\core;
use df\spur;

class EntryReader implements spur\feed\IEntryReaderPlugin {
    
    use spur\feed\TEntryReader;
    
    protected static $_xPathNamespaces = [
        'content' => 'http://purl.org/rss/1.0/modules/content/'
    ];
        
    public function getContent() {
        return $this->_xPath->evaluate(
            'string('.$this->_xPathPrefix.'/content:encoded)'
        );
    }
}