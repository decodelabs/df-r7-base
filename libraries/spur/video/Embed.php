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
    protected $_startTime;
    protected $_endTime;
    protected $_duration;
    protected $_autoPlay = false;

    public static function parse($embed) {
        $embed = trim($embed);

        $parts = explode('<', $embed, 2);

        if(count($parts) == 2) {
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
                    throw new UnexpectedValueException(
                        'Don\'t know how to parse this video embed'
                    );
            }
        } else {
            // check is url
            $output = new self($embed);
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

        $this->_url = $url;
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


// Duration
    public function setStartTime($seconds) {
        $this->_startTime = (int)$seconds;

        if(!$this->_startTime) {
            $this->_startTime = null;
        }

        return $this;
    }

    public function getStartTime() {
        return $this->_startTime;
    }

    public function setEndTime($seconds) {
        $this->_endTime = (int)$seconds;

        if(!$this->_endTime) {
            $this->_endTime = null;
        } else {
            $this->_duration = null;
        }

        return $this;
    }

    public function getEndTime() {
        return $this->_endTime;
    }

    public function setDuration($seconds) {
        $this->_duration = (int)$seconds;

        if(!$this->_duration) {
            $this->_duration = null;
        } else {
            $this->_endTime = null;
        }

        return $this;
    }

    public function getDuration() {
        return $this->_duration;
    }


// Auto play
    public function shouldAutoPlay($flag=null) {
        if($flag !== null) {
            $this->_autoPlay = (bool)$flag;
            return $this;
        }

        return $this->_autoPlay;
    }


// String
    public function render() {
        $func = '_prepareGenericUrl';

        foreach(self::$_urlMap as $search => $key) {
            if(false !== stripos($this->_url, $search)) {
                $func = '_prepare'.ucfirst($key).'Url';
                break;
            }
        }

        $tag = new aura\html\Element('iframe', null, [
            'src' => $this->$func($this->_url),
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
        return halo\protocol\http\Url::factory($url);
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
        
        $output = new halo\protocol\http\Url('http://www.youtube.com/embed/'.$id);

        if($this->_startTime !== null) {
            $output->query->start = $this->_startTime;
        }

        if($this->_endTime !== null) {
            $output->query->end = $this->_endTime;
        }

        if($this->_duration !== null) {
            $output->query->end = $this->_duration + $this->_startTime;
        }

        if($this->_autoPlay) {
            $output->query->autoplay = 1;
        }

        return $output;
    }

    protected function _prepareVimeoUrl($url) {
        $urlObj = halo\protocol\http\Url::factory($url);
        $id = $urlObj->path->getLast();

        if(!is_numeric($id)) {
            return $url;
        }

        $output = new halo\protocol\http\Url('http://player.vimeo.com/video/'.$id);

        if($this->_autoPlay) {
            $output->query->autoplay = 1;
        }

        /*
        if($this->_startTime !== null) {
            $output->query->start = $this->_startTime.'s';
        }

        if($this->_endTime !== null) {
            $output->query->end = $this->_endTime.'s';
        }

        if($this->_duration !== null) {
            $output->query->end = $this->_duration + $this->_startTime;
        }
        */

        return $output;
    }
}