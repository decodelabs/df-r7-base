<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\feed\extension\creativeCommons;

use df;
use df\core;
use df\spur;

class FeedReader implements spur\feed\IFeedReaderPlugin {
    
    use spur\feed\TFeedReader;
    
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
            'channel/cc:license'
        );
        
        if($list->length) {
            foreach($list as $license) {
                $licenses[] = trim($license->nodeValue);
            }
        }
        
        return $licenses;
    }
}