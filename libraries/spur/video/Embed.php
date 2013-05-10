<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\video;

use df;
use df\core;
use df\spur;
use df\halo;
use df\aura;
    
class Embed implements IVideoEmbed {

    use core\TStringProvider;

    protected static $_urlMap = [
        'youtube' => 'youtube',
        'youtu.be' => 'youtube',
        'vimeo' => 'vimeo'
    ];

    protected $_url;
    protected $_width = 640;
    protected $_height = 360;
    protected $_allowFullScreen = true;

    public static function parse($embed) {
        $embed = trim($embed);

        $parts = explode('<', $embed, 2);
        $embed = '<'.array_pop($parts);

        if(!preg_match('/^\<([a-zA-Z0-9\-]+) /i', $embed, $matches)) {
            core\stub($embed);
        }

        $tag = strtolower($matches[1]);

        switch($tag) {
            case 'iframe':
            case 'object':
                if(!preg_match('/src\=\"([^\"]+)\"/i', $embed, $matches)) {
                    throw new UnexpectedValueException(
                        'Could not extract source from flash embed'
                    );
                }

                $url = trim($matches[1]);
                $output = new self($url);

                if(preg_match('/width\=\"([^\"]+)\"/i', $embed, $matches)) {
                    $width = $matches[1];

                    if(preg_match('/height\=\"([^\"]+)\"/i', $embed, $matches)) {
                        $height = $matches[1];
                    } else {
                        $height = round(($width / $output->_width) * $output->_height);
                    }

                    $output->setWidth($width);
                    $output->setHeight($height);
                }

                break;

            default:
                core\stub($embed);
        }

        return $output;
    }


    public function __construct($url, $width=null, $height=null) {
        $this->setUrl($url);

        if($width !== null) {
            $this->setWidth($width);
        }

        if($height !== null) {
            $this->setHeight($height);
        }
    }

// Url
    public function setUrl($url) {
        $url = str_replace('&amp;', '&', $url);

        if(false !== strpos($url, '&') && false === strpos($url, '?')) {
            $parts = explode('&', $url, 2);
            $url = implode('?', $parts);
        }

        $func = '_prepareGenericUrl';

        foreach(self::$_urlMap as $search => $key) {
            if(false !== stripos($url, $search)) {
                $func = '_prepare'.ucfirst($key).'Url';
                break;
            }
        }

        $this->_url = $this->$func($url);
        return $this;
    }

    public function getUrl() {
        return $this->_url;
    }
 
 // Width
    public function setWidth($width) {
        $this->_width = (int)$width;
        return $this;
    }

    public function scaleWidth($width) {
        $width = (int)$width;
        $this->_height = round(($width / $this->_width) * $this->_height);
        $this->_width = $width;

        return $this;
    }

    public function getWidth() {
        return $this->_width;
    }

// Height
    public function setHeight($height) {
        $this->_height = (int)$height;
        return $this;
    }

    public function getHeight() {
        return $this->_height;
    }

    public function setDimensions($width, $height=null) {
        $width = (int)$width;
        $height = (int)$height;

        if(!$height) {
            if($width) {
                return $this->scaleWidth($width);
            } else {
                return $this;
            }
        }

        if(!$width) {
            $width = round(($height / $this->_height) * $this->_width);
        }

        $this->_width = $width;
        $this->_height = $height;

        return $this;
    }

// Full screen
    public function shouldAllowFullScreen($flag=null) {
        if($flag !== null) {
            $this->_allowFullScreen = (bool)$flag;
            return $this;
        }

        return $this->_allowFullScreen;
    }


// String
    public function render() {
        $tag = new aura\html\Element('iframe', null, [
            'src' => $this->_url,
            'width' => $this->_width,
            'height' => $this->_height,
            'frameborder' => 0
        ]);

        if($this->_allowFullScreen) {
            $tag->setAttribute('allowfullscreen', true);
            $tag->setAttribute('webkitAllowFullScreen', true);
            $tag->setAttribute('mozallowfullscreen', true);
        }

        return $tag;
    }

    public function toString() {
        return $this->render()->toString();
    }


// Url prepare
    protected function _prepareGenericUrl($url) {
        return $url;
    }

    protected function _prepareYoutubeUrl($url) {
        $url = halo\protocol\http\Url::factory($url);

        if(isset($url->query->v)) {
            $id = $url->query['v'];
        } else {
            $id = $url->path->getLast();

            if($id == 'watch') {
                core\stub($url);
            }
        }
        
        return 'http://www.youtube.com/embed/'.$id;
    }

    protected function _prepareVimeoUrl($url) {
        $urlObj = halo\protocol\http\Url::factory($url);
        $id = $urlObj->path->getLast();

        if(!is_numeric($id)) {
            return $url;
        }

        return 'http://player.vimeo.com/video/'.$id;
    }
}