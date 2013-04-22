<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\view;

use df;
use df\core;
use df\aura;
use df\arch;

class Html extends Base implements IHtmlView {
    
    use TLayoutView;
    
    const DEFAULT_TITLE = '*untitled';
    const DEFAULT_LAYOUT = 'Default';
    
    private static $_priorityMeta = array('x-ua-compatible', 'content-type');
    private static $_httpMeta = array(
        'allow', 'alternates', 'bulletin-date', 'bulletin-text', 'cache-control', 'content-base',
        'content-disposition', 'content-encoding', 'content-language', 'content-length', 'content-location',
        'content-md5', 'content-range', 'content-script-type', 'content-style-type', 'content-type', 
        'date', 'default-style', 'derived-from', 'etag', 'expires', 'ext-cache', 'instance-delegate',
        'instance-key', 'imagetoolbar', 'last-modified', 'link', 'location', 'mime-version', 
        'page-enter', 'page-exit', 'pics-label', 'pragma', 'public', 'range', 'refresh', 'server', 
        'set-cookie', 'site-enter', 'site-exit', 'title', 'transfer-encoding', 'uri', 'vary', 'via', 
        'warning', 'window-target'
    ); 
    
    protected $_title;
    protected $_titlePrefix;
    protected $_titleSuffix;
    protected $_baseHref;
    
    protected $_meta = array();
    
    protected $_css;
    protected $_styles;
    
    protected $_headJs;
    protected $_footJs;
    protected $_headScripts = array();
    protected $_footScripts = array();
    
    protected $_feeds = array();
    protected $_faviconHref;
    
    public $bodyTag;
    
    protected $_shouldRenderBase = true;
    
    public function __construct($type, arch\IContext $context) {
        parent::__construct($type, $context);
        
        $this->bodyTag = new aura\html\Tag('body');
        
        $this->_baseHref = $context->normalizeOutputUrl('/');

        $this
            ->setMeta('X-UA-Compatible', 'IE=edge,chrome=1')
            ->setMeta('content-type', $this->getContentType())
            ->setMeta('viewport', 'width=device-width')//; initial-scale=1.0; maximum-scale=1.0;')
            ;
    }


// Tags
    public function getBodyTag() {
        return $this->bodyTag;
    }
    
    
// Title
    public function setTitle($title) {
        $this->_title = (string)$title;
        
        if(empty($this->_title)) {
            $this->_title = null;
        }
        
        return $this;
    }

    public function getTitle() {
        return $this->_title;
    }
    
    public function hasTitle() {
        return $this->_title !== null;
    }

    public function setTitlePrefix($prefix) {
        $this->_titlePrefix = $prefix;
        return $this;
    }

    public function getTitlePrefix() {
        return $this->_titlePrefix;
    }

    public function hasTitlePrefix() {
        return $this->_titlePrefix !== null;
    }
    
    public function setTitleSuffix($suffix) {
        $this->_titleSuffix = $suffix;
        return $this;
    }
    
    public function getTitleSuffix() {
        return $this->_titleSuffix;
    }
    
    public function hasTitleSuffix() {
        return $this->_titleSuffix !== null;
    }

    public function getFullTitle() {
        return $this->_titlePrefix.$this->_title.$this->_titleSuffix;
    }
    
    
// Base
    public function setBaseHref($url) {
        $this->_baseHref = $this->_context->normalizeOutputUrl($url);
        return $this;
    }
    
    public function getBaseHref() {
        return $this->_baseHref;
    }
    
    
// Meta
    public function setMeta($key, $value) {
        if($value === null) {
            unset($this->_meta[$key]);
        } else {
            $this->_meta[$key] = (string)$value;
        }
        
        return $this;
    }
    
    public function getMeta($key) {
        if(isset($this->_meta[$key])) {
            return $this->_meta[$key];
        }
        
        return null;
    }
    
    public function hasMeta($key) {
        return isset($this->_meta[$key]);
    }
    
    public function removeMeta($key) {
        unset($this->_meta[$key]);
        return $this;
    }
    
    
    
// Keywords
    public function setKeywords($keywords) {
        $this->removeMeta('keywords');
        return $this->addKeywords($keywords);
    }
    
    public function addKeywords($keywords) {
        if(!is_array($keywords)) {
            $keywords = explode(' ', $keywords);
        }
        
        if(empty($keywords)) {
            return $this;
        }
        
        if($this->hasMeta('keywords')) {
            $current = explode(' ', $this->getMeta('keywords'));
        } else {
            $current = array();
        }
        
        return $this->setMeta('keywords', implode(' ', array_unique(array_merge($current, $keywords))));
    }
     
    public function getKeywords() {
        return $this->getMeta('keywords');
    }
    
    public function hasKeywords() {
        return $this->hasMeta('keywords');
    }
    
    public function hasKeyword($keyword) {
        if(!$this->hasMeta('keywords')) {
            return false;
        }
        
        return in_array($keyword, explode(' ', $this->getMeta('keywords')));
    }
    
    public function removeKeyword($keyword) {
        if(!$this->hasMeta('keywords')) {
            return $this;
        }
        
        $keywords = array_flip(explode(' ', $this->getMeta('keywords')));
        unset($keywords[$keyword]);
        return $this->setMeta('keywords', implode(' ', array_keys($keywords)));
    }
    
    public function removeKeywords() {
        return $this->removeMeta('keywords');
    }
    
    
// Robots
    public function canIndex($flag=null, $bot='robots') {
        if(is_string($flag)) {
            $bot = $flag;
            $flag = null;
        }
        
        if($this->hasMeta($bot)) {
            $current = explode(',', $this->getMeta($bot));
        } else {
            $current = array();
        }
        
        $current = array_flip($current);
        
        if($flag !== null) {
            if(!(bool)$flag) {
                unset($current['index']);
                $current['noindex'] = null;
            } else {
                unset($current['noindex']);
                $current['index'] = null;
            }
            
            return $this->setMeta($bot, implode(',', array_keys($current)));
        }
        
        return !array_key_exists('noindex', $current);
    }
    
    public function canFollow($flag=null, $bot='robots') {
        if(is_string($flag)) {
            $bot = $flag;
            $flag = null;
        }
        
        if($this->hasMeta($bot)) {
            $current = explode(',', $this->getMeta($bot));
        } else {
            $current = array();
        }
        
        $current = array_flip($current);
        
        if($flag !== null) {
            if(!(bool)$flag) {
                unset($current['follow']);
                $current['nofollow'] = null;
            } else {
                unset($current['nofollow']);
                $current['follow'] = null;
            }
            
            return $this->setMeta($bot, implode(',', array_keys($current)));
        }
        
        return !array_key_exists('nofollow', $current);
    }
    
    public function setRobots($value) {
        return $this->setMeta('robots', $value);
    }
    
    public function getRobots() {
        return $this->getMeta('robots');
    }
    
    public function hasRobots() {
        return $this->hasMeta('robots');
    }
    
    public function removeRobots() {
        return $this->removeMeta('robots');
    }
    
    // Favicon
    public function setFaviconHref($url) {
        $this->_faviconHref = $url;
        return $this;
    }
    
    public function getFaviconHref() {
        return $this->_faviconHref;
    }
    
    public function linkFavicon($uri) {
        $this->setFaviconHref($this->_context->normalizeOutputUrl($uri));
        return $this;
    }
    
    
    
// CSS
    public function linkCss($uri, $media=null, $weight=null, array $attributes=null, $condition=null) {
        if(!$this->_css) {
            $this->_css = new \SplPriorityQueue();
        }

        if($weight === null) {
            $weight = 50;
        }

        if(!$attributes) {
            $attributes = array();
        }

        $attributes['href'] = $this->_context->normalizeOutputUrl($uri);
        $attributes['rel'] = 'stylesheet';
        $attributes['type'] = 'text/css';
        
        if($media !== null) {
            $attributes['media'] = $media;
        }        
        
        $this->_css->insert([
            'tag' => new aura\html\Tag('link', $attributes),
            'condition' => $condition
        ], $weight);
        
        return $this;
    }

    public function linkConditionalCss($condition, $uri, $media=null, $weight=null, array $attributes=null) {
        return $this->linkCss($uri, $media, $weight, $attributes, $condition);
    }

    public function getCss() {
        $output = array();
        
        if($this->_css) {
            foreach(clone $this->_css as $tag) {
                $output[] = $tag;
            }
        }
        
        return $output;
    }
    
    public function clearCss() {
        $this->_css = null;
        return $this;
    }
    
    
// Styles
    public function setStyles($styles) {
        $this->getStyles()->clear()->import($styles);
        return $this;
    }
    
    public function addStyles($styles) {
        $this->getStyles()->import($styles);
        return $this;
    }
    
    public function getStyles() {
        if(!$this->_styles) {
            $this->_styles = new aura\html\StyleBlock();
        }
        
        return $this->_styles;
    }
    
    public function hasStyles() {
        return $this->_styles !== null && !$this->_styles->isEmpty();
    }
    
    public function removeStyles() {
        $this->_styles = null;
        return $this;
    }
    
    public function setStyle($key, $value) {
        $this->getStyles()->set($key, $value);
        return $this;
    }
    
    public function getStyle($key, $default=null) {
        if(!$this->_styles) {
            return $default;
        }
        
        return $this->_styles->get($key, $default);
    }
    
    public function removeStyle($key) {
        if($this->_styles) {
            $this->_styles->remove($key);
        }
        
        return $this;
    }
    
    public function hasStyle($key) {
        if(!$this->_styles) {
            return false;
        }
        
        return $this->_styles->has($key);
    }
    
    
    
// Js
    public function linkJs($uri, $weight=null, array $attributes=null, $fallbackScript=null, $condition=null) {
        return $this->linkHeadJs($uri, $weight, $attributes, $fallbackScript, $condition);
    }

    public function linkConditionalJs($condition, $uri, $weight=null, array $attributes=null, $fallbackScript=null) {
        return $this->linkHeadJs($uri, $weight, $attributes, $fallbackScript, $condition);
    }

    public function linkHeadJs($uri, $weight=null, array $attributes=null, $fallbackScript=null, $condition=null) {
        if(!$this->_headJs) {
            $this->_headJs = new \SplPriorityQueue();
        }

        if($weight === null) {
            $weight = count($this->_headJs) + 20;
        }

        $this->_headJs->insert($this->_createJsEntry($uri, $attributes, $fallbackScript, $condition), $weight);

        return $this;
    }

    public function linkConditionalHeadJs($condition, $uri, $weight=null, array $attributes=null, $fallbackScript=null) {
        return $this->linkHeadJs($uri, $weight, $attributes, $fallbackScript, $condition);
    }

    public function linkFootJs($uri, $weight=null, array $attributes=null, $fallbackScript=null, $condition=null) {
        if(!$this->_footJs) {
            $this->_footJs = new \SplPriorityQueue();
        }

        if($weight === null) {
            $weight = count($this->_footJs) + 20;
        }

        $this->_footJs->insert($this->_createJsEntry($uri, $attributes, $fallbackScript, $condition), $weight);
        
        return $this;
    }

    public function linkConditionalFootJs($condition, $uri, $weight=null, array $attributes=null, $fallbackScript=null) {
        return $this->linkFootJs($uri, $weight, $attributes, $fallbackScript, $condition);
    }
    
    protected function _createJsEntry($uri, array $attributes=null, $fallbackScript, $condition) {
        if(!$attributes) {
            $attributes = array();
        }

        $attributes['src'] = $this->_context->normalizeOutputUrl($uri);
        $attributes['type'] = 'text/javascript';

        return [
            'tag' => new aura\html\Tag('script', $attributes),
            'condition' => $condition,
            'fallback' => $fallbackScript
        ];
    }
    
    public function getJs() {
        return array_merge(
            $this->getHeadJs(),
            $this->getFootJs()
        );
    }
    
    public function getHeadJs() {
        $output = array();
        
        if($this->_headJs) {
            foreach(clone $this->_headJs as $tag) {
                $output[] = $tag;
            }
        }
        
        return $output;
    }

    public function getFootJs() {
        $output = array();
        
        if($this->_footJs) {
            foreach(clone $this->_footJs as $tag) {
                $output[] = $tag;
            }
        }
        
        return $output;
    }


    public function clearJs() {
        return $this->clearHeadJs()->clearFootJs();
    }

    public function clearHeadJs() {
        $this->_headJs = null;
        return $this;
    }
    
    public function clearFootJs() {
        $this->_footJs = null;
        return $this;
    }
    
    
// Scripts
    public function addScript($id, $script, $condition=null) {
        return $this->addHeadScript($id, $script, $condition);
    }

    public function addHeadScript($id, $script, $condition=null) {
        $this->_headScripts[$id] = [
            'script' => $script,
            'condition' => $condition
        ];

        return $this;
    }

    public function addFootScript($id, $script, $condition=null) {
        $this->_footScripts[$id] = [
            'script' => $script,
            'condition' => $condition
        ];

        return $this;
    }
    
    public function removeScript($id) {
        return $this->removeHeadScript($id)->removeFootScript($id);
    }

    public function removeHeadScript($id) {
        unset($this->_headScripts[$id]);
        return $this;
    }

    public function removeFootScript($id) {
        unset($this->_footScripts[$id]);
        return $this;
    }

    public function clearScripts() {
        return $this->clearHeadScripts()->clearFootScripts();
    }

    public function clearHeadScripts() {
        $this->_headScripts = array();
        return $this;
    }

    public function clearFootScripts() {
        $this->_footScripts = array();
        return $this;
    }
    
// Content
    public function getContentType() {
        return 'text/html; charset=utf-8';
    }
    
// Rendering
    public function shouldRenderBase($flag=null) {
        if($flag !== null) {
            $this->_shouldRenderBase = (bool)$flag;
            return $this;
        }
        
        return $this->_shouldRenderBase;
    }

    protected function _beforeRender() {
        if(empty($this->_title) && empty($this->_titlePrefix) && empty($this->_titleSuffix)) {
            $this->setTitle(static::DEFAULT_TITLE);
        }
        
        if(!$this->_context->request->isArea('front')) {
            $this->canIndex(false)->canFollow(false);
        }
    }
    
    public function render() {
        $output = parent::render();
        
        if($this->_shouldRenderBase) {
            $output = 
                '<!DOCTYPE html>'."\n".
                '<html lang="en" class="no-js">'."\n".
                $this->_renderHead()."\n".
                $this->bodyTag->open()."\n".
                $output."\n".
                $this->_renderJsList($this->_footJs).
                $this->_renderScriptList($this->_footScripts).
                $this->bodyTag->close()."\n".
                '</html>';
        }
        
        return $output;
    }

    protected function _renderHead() {
        $output = '<head>'."\n";
        
        $meta = $this->_meta;
        
        // Priority meta
        foreach($meta as $key => $value) {
            if(in_array(strtolower($key), self::$_priorityMeta)) {
                $output .= '    '.$this->_metaToString($key, $meta[$key])."\n";
                unset($meta[$key]);
            }
        }
        
        // Title
        $output .= '    <title>'.$this->esc($this->getFullTitle()).'</title>'."\n";
        
        // Base
        if($this->_baseHref !== null) {
            $output .= '    <base href="'.$this->esc($this->_baseHref).'" />'."\n"; 
        }
        
        // Favicon
        if($this->_faviconHref !== null) {
            $output .= '    <link rel="shortcut icon" href="'.$this->esc($this->_faviconHref).'" />'."\n";//type="'.core\mime\Type::fileToMime($this->_faviconUrl).'" />'."\n";
        }

        // Meta
        foreach($meta as $key => $value) {
            if($value !== null) {
                $output .= '    '.$this->_metaToString($key, $value)."\n";
            }    
        }
        
        // Css
        $output .= $this->_renderCssList($this->_css);

        // Style
        if($this->_styles) {
            $output .= '    '.str_replace("\n", "\n    ", $this->_styles->toString())."\n";
        }
        
        // Js
        $output .= $this->_renderJsList($this->_headJs);

        // Scripts
        $output .= $this->_renderScriptList($this->_headScripts);
        
        $output .= '</head>'."\n";
        return $output;
    }

    protected function _renderCssList($list) {
        if(!$list) {
            return null;
        }

        $output = '';

        foreach(clone $list as $entry) {
            $line = '    '.$entry['tag']->__toString()."\n";

            if($entry['condition']) {
                $line = $this->_addCondition($line, $entry['condition']);
            }

            $output = $line.$output;
        }

        return $output;
    }


    protected function _renderJsList($list) {
        if(!$list) {
            return null;
        }

        $output = '';

        foreach(clone $list as $entry) {
            $line = '    '.$entry['tag']->open().$entry['tag']->close()."\n";

            if(isset($entry['fallback'])) {
                $line .= $this->_renderScriptList([['script' => $entry['fallback']]]);
            }

            if(isset($entry['condition'])) {
                $line = $this->_addCondition($line, $entry['condition']);
            }

            $output = $line.$output;
        }

        return $output;
    }

    protected function _renderScriptList(array $scripts) {
        if(empty($scripts)) {
            return null;
        }
        
        $output = '';

        foreach($scripts as $entry) {
            $line = '    <script type="text/javascript">'.
                    "\n        ".str_replace("\n", "\n        ", $entry['script'])."\n".
                    '    </script>'."\n";

            if(isset($entry['condition'])) {
                $line = $this->_addCondition($line, $entry['condition']);
            }

            $output .= $line;
        }

        return $output;
    }

    protected function _addCondition($line, $condition) {
        return '    <!--[if '.$condition.' ]>'.trim($line).'<![endif]-->'."\n";
    }

    protected function _metaToString($key, $value) {
        if(in_array(strtolower($key), self::$_httpMeta)) {
            return '<meta http-equiv="'.$this->esc($key).'" content="'.$this->esc($value).'" />';
        } else {
            return '<meta name="'.$this->esc($key).'" content="'.$this->esc($value).'" />';
        }
    }
}
