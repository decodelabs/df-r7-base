<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\feed\extension\wellFormedWeb;

use df;
use df\core;
use df\spur;

class EntryReader implements spur\feed\IEntryReaderPlugin {

    use spur\feed\TEntryReader;

    const XPATH_NAMESPACES = [
        'wfw' => 'http://wellformedweb.org/CommentAPI/'
    ];

    public function getCommentFeedLink() {
        return $this->_xPath->evaluate(
            'string('.$this->_xPathPrefix.'/wfw:commentRss)'
        );
    }
}