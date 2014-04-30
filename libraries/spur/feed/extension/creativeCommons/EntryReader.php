<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\feed\extension\creativeCommons;

use df;
use df\core;
use df\spur;

class EntryReader implements spur\feed\IEntryReaderPlugin {
    
    use spur\feed\TEntryReader;
    
    protected static $_xPathNamespaces = [
        'cc' => 'http://backend.userland.com/creativeCommonsRssModule'
    ];
        
    public function getLicense($index=0) {
        $licenses = $this->getLicenses();
        
        if(isset($licenses[$index])) {
            return $licenses[$index];
        }
        
        return null;
    }
    
    public function getLicenses() {
        $licenses = [];
        
        $list = $this->_xPath->evaluate(
            $this->_xPathPrefix.'//cc:license'
        );
        
        if($list->length) {
            foreach($list as $license) {
                $licenses[] = trim($license->nodeValue);
            }
        }
        
        return $licenses;
    }
}