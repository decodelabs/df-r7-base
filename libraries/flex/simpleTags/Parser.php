<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex\simpleTags;

use df;
use df\core;
use df\flex;
use df\aura;

class Parser {

    protected static $_tagList = [
        'a', 'abbr', 'b', 'br', 'cite', 'code', 'del', 'em', 'i', 'img', 'ins', 
        'q', 'small', 'span', 'strong', 'sub', 'sup', 'time', 'var'
    ];

    protected $_body;
    protected $_customTags = array();

    public function __construct($body, array $customTags=null) {
        $this->_body = $body;

        if(!empty($customTags)) {
            $this->setCustomTags($customTags);
        }
    }


// Custom tags
    public function setCustomTags(array $tags) {
        $this->_customTags = array();
        return $this->addCustomTags($tags);
    }

    public function addCustomTags(array $tags) {
        foreach($tags as $name => $callback) {
            $this->addCustomTag($name, $callback);
        }

        return $this;
    }

    public function addCustomTag($name, Callable $callback) {
        $this->_customTags[strtolower($name)] = $callback;
        return $this;
    }

    public function removeCustomTag($name) {
        unset($this->_customTags[strtolower($name)]);
        return $this;
    }

    public function getCustomTags() {
        return $this->_customTags;
    }

    public function clearCustomTags() {
        $this->_customTags = array();
        return $this;
    }


// Translate
    public function toHtml() {
        if(null === ($text = $this->_prepareBody())) {
            return null;
        }

        $text = '<p>'.str_replace("\n\n", '</p>'.'<p>', $text).'</p>';
        $text = str_replace(["\n", '</p>'], ['<br />', '</p>'."\n"], trim($text));

        return $text;
    }

    public function toInlineHtml() {
        if(null === ($text = $this->_prepareBody())) {
            return null;
        }

        $text = str_replace("\n", '<br />'."\n", $text);
        
        return $text;
    }

    protected function _prepareBody() {
        $text = trim($this->_body);

        if(empty($text) && $text !== '0') {
            return null;
        }

        $tags = [];

        foreach(self::$_tagList as $tag) {
            $tags[] = '<'.$tag.'>';
        }

        foreach($this->_customTags as $name => $callback) {
            $tags[] = '<'.$name.'>';
        }

        $text = strip_tags($text, implode('', $tags));
        $length = strlen($text);
        $output = '';
        $last = null;

        for($i = 0; $i < $length; $i++) {
            $char = $text{$i};
            $next = @$text{$i+1};

            if($char == ' ') {
                $rep = ' ';

                if($last == "\n"
                || $last == ' '
                || $next == ' ') {
                    $rep = '&nbsp;';
                }

                $output .= $rep;
            } else {
                $output .= $char;
            }

            $last = $char;
        }

        foreach($this->_customTags as $name => $callback) {
            $newName = null;

            $output = preg_replace_callback(
                '/<'.$name.'([^>]*)\/?>/i', 
                function($matches) use($name, $callback, &$newName) {
                    $attributes = array();

                    if(preg_match_all('/([a-zA-Z0-9]+)\="([^"]*)"/', $matches[1], $attrMatches)) {
                        foreach($attrMatches[1] as $i => $key) {
                            $attributes[$key] = $attrMatches[2][$i];
                        }
                    }
                    
                    $tag = $callback($attributes);

                    if(!$tag) {
                        return '';
                    }

                    if(is_string($tag)) {
                        $tag = new aura\html\Tag($tag);
                    } else if(!$tag instanceof aura\html\ITag) {
                        return '';
                    }

                    if($newName === null) {
                        $newName = $tag->getName();
                    } else if($newName != $tag->getName()) {
                        throw new \UnexpectedValueException(
                            'Custom tag replacement names must be constant throughout a document - found "'.$newName.'" and "'.$tag->getName().'" as tag names'
                        );
                    }

                    return $tag->open();
                }, 
                $output
            );

            if($newName) {
                $output = preg_replace('/<\/'.$name.'>/i', '</'.$newName.'>', $output);
            } else {
                $output = preg_replace('/<\/'.$name.'>/i', '', $output);
            }
        }

        return $output;
    }
}