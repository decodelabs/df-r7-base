<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\video;

use df;
use df\core;
use df\spur;
use df\link;
use df\aura;

class Embed implements IVideoEmbed {

    use core\TStringProvider;

    const URL_MAP = [
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
    protected $_provider;
    protected $_source;

    public static function parse($embed) {
        $embed = trim($embed);

        $parts = explode('<', $embed, 2);

        if(count($parts) == 2) {
            $embed = '<'.array_pop($parts);

            if(!preg_match('/^\<([a-zA-Z0-9\-]+) /i', $embed, $matches)) {
                throw new UnexpectedValueException(
                    'Don\'t know how to parse this video embed'
                );
            }

            $tag = strtolower($matches[1]);

            switch($tag) {
                case 'iframe':
                case 'object':
                    if(!preg_match('/src\=(\"|\')([^\'"]+)(\"|\')/i', $embed, $matches)) {
                        throw new UnexpectedValueException(
                            'Could not extract source from flash embed'
                        );
                    }

                    $url = trim($matches[2]);
                    $output = new self($url, null, null, $embed);

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

                case 'script':
                    $output = new self(null, null, null, $embed);
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


    public function __construct($url, $width=null, $height=null, $embedSource=null) {
        $this->setUrl($url);

        if($width !== null) {
            $this->setWidth($width);
        }

        if($height !== null) {
            $this->setHeight($height);
        }

        $this->_source = $embedSource;
    }

// Url
    public function setUrl($url) {
        if(empty($url)) {
            $this->_url = null;
            return $this;
        }

        $url = str_replace('&amp;', '&', $url);

        if(false !== strpos($url, '&') && false === strpos($url, '?')) {
            $parts = explode('&', $url, 2);
            $url = implode('?', $parts);
        }

        $this->_url = $url;
        $this->_provider = null;

        foreach(self::URL_MAP as $search => $key) {
            if(false !== stripos($this->_url, $search)) {
                $this->_provider = $key;
                break;
            }
        }

        return $this;
    }

    public function getUrl() {
        return $this->_url;
    }

    public function getPreparedUrl() {
        $tag = $this->render();
        return $tag->getAttribute('src');
    }

    public function getProvider() {
        return $this->_provider;
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
    public function shouldAllowFullScreen(bool $flag=null) {
        if($flag !== null) {
            $this->_allowFullScreen = $flag;
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
    public function shouldAutoPlay(bool $flag=null) {
        if($flag !== null) {
            $this->_autoPlay = $flag;
            return $this;
        }

        return $this->_autoPlay;
    }


// String
    public function render() {
        if(($this->_url === null || !$this->_provider) && $this->_source !== null) {
            return new aura\html\Element('div.w-videoEmbed', new aura\html\ElementString($this->_source));
        }

        if($this->_provider) {
            $func = '_render'.ucfirst($this->_provider);
            $tag = $this->$func();
        } else {
            $tag = new aura\html\Element('iframe', null, [
                'src' => $this->_url,
                'width' => $this->_width,
                'height' => $this->_height,
                'frameborder' => 0
            ]);
        }

        $tag->addClass('w-videoEmbed');

        if($tag->getName() == 'iframe' && $this->_allowFullScreen) {
            $tag->setAttribute('allowfullscreen', true);
            $tag->setAttribute('webkitAllowFullScreen', true);
            $tag->setAttribute('mozallowfullscreen', true);
        }

        return $tag;
    }

    public function toString(): string {
        return $this->render()->toString();
    }


// Url prepare
    protected function _renderYoutube() {
        $url = link\http\Url::factory($this->_url);

        if(isset($url->query->v)) {
            $id = $url->query['v'];
        } else {
            $id = $url->path->getLast();

            if($id == 'watch') {
                core\stub($url);
            }
        }

        static $vars = [
            'autohide', 'autoplay', 'cc_load_policy', 'color', 'controls',
            'disablekb', 'enablejsapi', 'end', 'fs', 'hl', 'iv_load_policy',
            'list', 'listType', 'loop', 'modestbranding', 'origin', 'playerapiid',
            'playlist', 'playsinline', 'rel', 'showinfo', 'start', 'theme'
        ];

        $url = new link\http\Url('//www.youtube.com/embed/'.$id);

        foreach($url->query as $key => $node) {
            if(in_array(strtolower($key), $vars)) {
                $url->query->set($key, $node);
            }
        }

        if($this->_startTime !== null) {
            $url->query->start = $this->_startTime;
        }

        if($this->_endTime !== null) {
            $url->query->end = $this->_endTime;
        }

        if($this->_duration !== null) {
            $url->query->end = $this->_duration + $this->_startTime;
        }

        if($this->_autoPlay) {
            $url->query->autoplay = 1;
        }

        $tag = new aura\html\Element('iframe', null, [
            'src' => $url,
            'width' => $this->_width,
            'height' => $this->_height,
            'frameborder' => 0
        ]);

        return $tag;
    }

    protected function _renderVimeo() {
        $urlObj = link\http\Url::factory($this->_url);
        $id = $urlObj->path->getLast();

        if(!is_numeric($id)) {
            return $urlObj;
        }

        $url = new link\http\Url('//player.vimeo.com/video/'.$id);

        if($this->_autoPlay) {
            $url->query->autoplay = 1;
        }

        /*
        if($this->_startTime !== null) {
            $url->query->start = $this->_startTime.'s';
        }

        if($this->_endTime !== null) {
            $url->query->end = $this->_endTime.'s';
        }

        if($this->_duration !== null) {
            $url->query->end = $this->_duration + $this->_startTime;
        }
        */

        $tag = new aura\html\Element('iframe', null, [
            'src' => $url,
            'width' => $this->_width,
            'height' => $this->_height,
            'frameborder' => 0
        ]);

        return $tag;
    }
}