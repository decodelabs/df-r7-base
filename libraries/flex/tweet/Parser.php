<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex\tweet;

use df;
use df\core;
use df\flex;
use df\aura;
use df\arch;

class Parser {

    const REGEX_AT_SIGNS = '[@＠]';
    const REGEX_URL_CHARS_BEFORE = '(?:[^-\\/"\':!=a-z0-9_@＠]|^|\\:)';
    const REGEX_URL_DOMAIN = '(?:[^\\p{P}\\p{Lo}\\s][\\.-](?=[^\\p{P}\\p{Lo}\\s])|[^\\p{P}\\p{Lo}\\s])+\\.[a-z]{2,}(?::[0-9]+)?';
    const REGEX_PROBABLE_TLD = '/\\.(?:com|net|org|gov|edu)$/iu';
    const REGEX_URL_CHARS_PATH = '(?:(?:\\([a-z0-9!\\*\';:=\\+\\$\\/%#\\[\\]\\-_,~]+\\))|@[a-z0-9!\\*\';:=\\+\\$\\/%#\\[\\]\\-_,~]+\\/|[\\.\\,]?(?:[a-z0-9!\\*\';:=\\+\\$\\/%#\\[\\]\\-_~]|,(?!\s)))';
    const REGEX_URL_CHARS_PATH_END = '[a-z0-9=#\\/]';
    const REGEX_URL_CHARS_QUERY = '[a-z0-9!\\*\'\\(\\);:&=\\+\\$\\/%#\\[\\]\\-_\\.,~]';
    const REGEX_URL_CHARS_QUERY_END = '[a-z0-9_&=#\\/]';
    const REGEX_USERNAME_LIST = '/([^a-z0-9_\/]|^|RT:?)([@＠]+)([a-z0-9_]{1,20})(\/[a-z][-_a-z0-9\x80-\xFF]{0,24})?([@＠\xC0-\xD6\xD8-\xF6\xF8-\xFF]?)/iu';
    const REGEX_USERNAME_MENTION = '/(^|[^a-z0-9_])[@＠]([a-z0-9_]{1,20})([@＠\xC0-\xD6\xD8-\xF6\xF8-\xFF]?)/iu';
    const REGEX_HASHTAG = '/(^|[^0-9A-Z&\/\?]+)([#＃]+)([0-9A-Z_]*[A-Z_]+[a-z0-9_üÀ-ÖØ-öø-ÿ]*)/iu';
    const REGEX_WHITESPACE = '[\x09-\x0D\x20\x85\xA0]|\xe1\x9a\x80|\xe1\xa0\x8e|\xe2\x80[\x80-\x8a,\xa8,\xa9,\xaf\xdf]|\xe3\x80\x80';

    const CLASS_URL = 'url';
    const CLASS_HASHTAG = 'hashtag';
    const CLASS_LIST = 'list';
    const CLASS_USER = 'user';

    const URL_BASE = 'http://twitter.com/';
    const URL_HASHTAG = 'http://twitter.com/search?q=%23';
    
    protected static $_urlRegex = null;
    protected static $_replyUsernameRegex = null;

    protected $_body;


    public function __construct($body) {
        $this->_body = $body;

        if(self::$_urlRegex === null) {
            self::$_urlRegex = 
                '/(?:'.
                '('.self::REGEX_URL_CHARS_BEFORE.')'.
                '('.
                '((?:https?:\\/\\/|www\\.)?)'.
                '('.self::REGEX_URL_DOMAIN.')'.
                '(\\/'.self::REGEX_URL_CHARS_PATH.'*'.
                self::REGEX_URL_CHARS_PATH_END.'?)?'.
                '(\\?'.self::REGEX_URL_CHARS_QUERY.'*'.
                self::REGEX_URL_CHARS_QUERY_END.')?'.
                ')'.
                ')/iux';
        }

        if(self::$_replyUsernameRegex === null) {
            self::$_replyUsernameRegex = '/^('.self::REGEX_WHITESPACE.')*[@＠]([a-zA-Z0-9_]{1,20})/';
        }
    }

    public function toHtml() {
        $body = htmlspecialchars($this->_body, \ENT_QUOTES, 'UTF-8', false);

        // Urls
        $body = preg_replace_callback(self::$_urlRegex, function($matches) {
            list($all, $before, $url, $protocol, $domain, $path, $query) = array_pad($matches, 7, '');
            $url = htmlspecialchars($url, \ENT_QUOTES, 'UTF-8', false);

            if(!$protocol && !preg_match(self::REGEX_PROBABLE_TLD, $domain)) {
                return $all;
            }

            $href = ((!$protocol || strtolower($protocol) === 'www.') ? 'http://'.$url : $url);
            return $before.$this->_wrap($href, self::CLASS_URL, $url);
        }, $body);

        // Hashtags
        $body = preg_replace_callback(self::REGEX_HASHTAG, function($matches) {
            $replacement = $matches[1];
            $element = $matches[2].$matches[3];
            $url = self::URL_HASHTAG.$matches[3];
            $replacement .= $this->_wrap($url, self::CLASS_HASHTAG, $element);
            return $replacement;
        }, $body);

        // Usernames
        $body = preg_replace_callback(self::REGEX_USERNAME_LIST, function($matches) {
            list($all, $before, $at, $username, $listname, $after) = array_pad($matches, 6, '');

            if(!empty($after)) {
                return $all;
            }

            if(!empty($listname)) {
                $element = $username.substr($listname, 0, 26);
                $class = self::CLASS_LIST;
                $url = self::URL_BASE.$element;
                $suffix = substr($listname, 26);
            } else {
                $element = $username;
                $class = self::CLASS_USER;
                $url = self::URL_BASE.$element;
                $suffix = '';
            }

            return $before.$this->_wrap($url, $class, $at.$element).$suffix.$after;
        }, $body);

        return $body;
    }

    protected function _wrap($url, $class, $element) {
        $output = '<a';

        if($class) {
            $output .= ' class="'.$class.'"';
        }

        $output .= ' href="'.$url.'"';
        $output .= ' rel="external nofollow"';
        $output .= ' target="_blank"';
        $output .= '>'.$element.'</a>';
        return $output;
    }
}