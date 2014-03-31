<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\feed\extension\thread;

use df;
use df\core;
use df\spur;

class EntryReader implements spur\feed\IEntryReaderPlugin {
    
    use spur\feed\TEntryReader;
    
    protected static $_xPathNamespaces = array(
        'thread10' => 'http://purl.org/syndication/thread/1.0',
    );
        
    public function getCommentCount() {
        return $this->_xPath->evaluate(
            'string('.$this->_xPathPrefix.'/thread10:total)'
        );
    }
}