<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex\simpleTags;

use df;
use df\core;
use df\flex;
    
class Parser {

    protected static $_tagList = [
        'a', 'abbr', 'b', 'br', 'cite', 'code', 'del', 'em', 'i', 'img', 'ins', 
        'q', 'small', 'span', 'strong', 'sub', 'sup', 'time', 'var'
    ];

    protected $_body;

    public function __construct($body) {
        $this->_body = $body;
    }

    public function toHtml() {
        $text = trim($this->_body);

        if(empty($text) && $text !== '0') {
            return null;
        }

        $tags = [];

        foreach(self::$_tagList as $tag) {
            $tags[] = '<'.$tag.'>';
        }

        $text = strip_tags($text, implode('', $tags));
        $text = '<p>'.str_replace("\n\n", '</p>'."\n".'<p>', $text).'</p>';

        return $text;
    }
}