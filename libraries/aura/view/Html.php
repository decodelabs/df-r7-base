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
use df\flow;

class Html extends Base implements IHtmlView, core\IDumpable {

    use TResponseView;
    use TLayoutView;

    const DEFAULT_TITLE = '*untitled';
    const DEFAULT_LAYOUT = 'Default';

    private static $_priorityMeta = ['x-ua-compatible', 'content-type'];
    private static $_httpMeta = [
        'allow', 'alternates', 'bulletin-date', 'bulletin-text', 'cache-control', 'content-base',
        'content-disposition', 'content-encoding', 'content-language', 'content-length', 'content-location',
        'content-md5', 'content-range', 'content-script-type', 'content-style-type', 'content-type',
        'date', 'default-style', 'derived-from', 'etag', 'expires', 'ext-cache', 'instance-delegate',
        'instance-key', 'imagetoolbar', 'last-modified', 'link', 'location', 'mime-version',
        'page-enter', 'page-exit', 'pics-label', 'pragma', 'public', 'range', 'refresh', 'server',
        'set-cookie', 'site-enter', 'site-exit', 'title', 'transfer-encoding', 'uri', 'vary', 'via',
        'warning', 'window-target', 'x-ua-compatible'
    ];

    protected $_title;
    protected $_titlePrefix;
    protected $_titleSuffix;
    protected $_baseHref;

    protected $_meta = [];
    protected $_data = [];

    protected $_css = [];
    protected $_cssMaxWeight = 0;
    protected $_styles;

    protected $_js = [];
    protected $_jsMaxWeight = 0;

    protected $_headScripts = [];
    protected $_footScripts = [];

    protected $_links = [];

    public $htmlTag;
    public $bodyTag;

    protected $_shouldRenderBase = true;

    public function __construct($type, arch\IContext $context) {
        parent::__construct($type, $context);

        $this->htmlTag = new aura\html\Tag('html', ['lang' => 'en']);
        $this->bodyTag = new aura\html\Tag('body');

        //$this->_baseHref = $this->uri->__invoke('/');

        $this
            ->setMeta('X-UA-Compatible', 'IE=edge,chrome=1')
            ->setMeta('content-type', $this->getContentType())
            //->setMeta('viewport', 'width=device-width, initial-scale=1.0, maximum-scale=1.0')
            ;
    }


// Tags
    public function getHtmlTag() {
        return $this->htmlTag;
    }

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

    public function setFullTitle($title) {
        return $this->setTitle($title)->setTitlePrefix(false)->setTitleSuffix(false);
    }

    public function getFullTitle() {
        return $this->_titlePrefix.$this->_title.$this->_titleSuffix;
    }


// Base
    public function setBaseHref($url) {
        $this->_baseHref = $this->uri->__invoke($url);
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


// Data
    public function setData($key, $value) {
        if($value === null) {
            unset($this->_data[$key]);
        } else {
            $this->_data[$key] = (string)$value;
        }

        return $this;
    }

    public function getData($key) {
        if(isset($this->_data[$key])) {
            return $this->_data[$key];
        }

        return null;
    }

    public function hasData($key) {
        return isset($this->_data[$key]);
    }

    public function removeData($key) {
        unset($this->_data[$key]);
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
            $current = [];
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
            $current = [];
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
            $current = [];
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


// Link
    public function addLink($id, $rel, $url, array $attr=null) {
        $attributes = [
            'rel' => $rel,
            'href' => $this->uri($url)
        ];

        if($attr) {
            $attributes = array_merge($attributes, $attr);
        }

        $this->_links[$id] = $this->html('link', null, $attributes);
        return $this;
    }

    public function getLinks() {
        return $this->_links;
    }

    public function getLink($id) {
        if(isset($this->_links[$id])) {
            return $this->_links[$id];
        }
    }

    public function removeLink($id) {
        unset($this->_links[$id]);
        return $this;
    }

    public function clearLinks() {
        $this->_links = [];
        return $this;
    }



// Favicon
    public function setFaviconHref($url) {
        if(!isset($this->_links['favicon'])) {
            $this->addLink('favicon', 'shortcut icon', $url);
        } else {
            $this->_links['favicon']->setAttribute('href', $this->uri($url));
        }

        return $this;
    }

    public function getFaviconHref() {
        if(isset($this->_links['favicon'])) {
            return $this->_links['favicon']->getAttribute('href');
        }
    }

    public function linkFavicon($url) {
        return $this->addLink('favicon', 'shortcut icon', $url);
    }



// CSS
    public function linkCss($uri, $weight=null, array $attributes=null, $condition=null) {
        $url = $this->uri($uri);

        if($weight === null) {
            $weight = ++$this->_cssMaxWeight;
        } else if($weight > $this->_cssMaxWeight) {
            $this->_cssMaxWeight = $weight;
        }

        $entry = [
            'weight' => $weight,
            'url' => $url
        ];

        if($attributes !== null) {
            $entry['attributes'] = $attributes;
        }

        if($condition !== null) {
            $entry['condition'] = $condition;
        }

        $url = (string)$url;

        if(isset($this->_css[$url])) {
            $this->_css[$url] = array_merge($this->_css[$url], $entry);
        } else {
            $this->_css[$url] = $entry;
        }

        return $this;
    }

    public function linkConditionalCss($condition, $uri, $weight=null, array $attributes=null) {
        return $this->linkCss($uri, $weight, $attributes, $condition);
    }

    public function getCss() {
        return $this->_css;
    }

    public function clearCss() {
        $this->_css = [];
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
    public function linkJs($uri, $weight=null, array $attributes=null, $condition=null) {
        return $this->_linkJs('head', $uri, $weight, $attributes, $condition);
    }

    public function linkConditionalJs($condition, $uri, $weight=null, array $attributes=null) {
        return $this->_linkJs('head', $uri, $weight, $attributes, $condition);
    }

    public function linkFootJs($uri, $weight=null, array $attributes=null, $condition=null) {
        return $this->_linkJs('foot', $uri, $weight, $attributes, $condition);
    }

    public function linkConditionalFootJs($condition, $uri, $weight=null, array $attributes=null) {
        return $this->_linkJs('foot', $uri, $weight, $attributes, $condition);
    }


    protected function _linkJs($location, $uri, $weight=null, array $attributes=null, $condition=null) {
        $url = $this->uri($uri);

        if($weight === null) {
            $weight = ++$this->_jsMaxWeight;
        } else if($weight > $this->_jsMaxWeight) {
            $this->_jsMaxWeight = $weight;
        }

        $entry = [
            'weight' => $weight,
            'url' => $url,
            'location' => $location
        ];

        if($attributes !== null) {
            $entry['attributes'] = $attributes;
        }

        if($condition !== null) {
            $entry['condition'] = $condition;
        }

        $url = (string)$url;

        if(isset($this->_js[$url])) {
            $this->_js[$url] = array_merge($this->_js[$url], $entry);
        } else {
            $this->_js[$url] = $entry;
        }

        return $this;
    }


    public function getJs() {
        return $this->_js;
    }

    public function getHeadJs() {
        $output = [];

        foreach($this->_js as $url => $entry) {
            if($entry['location'] != 'head') {
                continue;
            }

            $output[$url] = $entry;
        }

        return $output;
    }

    public function getFootJs() {
        $output = [];

        foreach($this->_js as $url => $entry) {
            if($entry['location'] != 'foot') {
                continue;
            }

            $output[$url] = $entry;
        }

        return $output;
    }


    public function clearJs() {
        $this->_js = [];
        return $this;
    }

    public function clearHeadJs() {
        foreach($this->_js as $url => $entry) {
            if($entry['location'] == 'head') {
                unset($this->_js[$url]);
            }
        }

        return $this;
    }

    public function clearFootJs() {
        foreach($this->_js as $url => $entry) {
            if($entry['location'] == 'foot') {
                unset($this->_js[$url]);
            }
        }

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
        $this->_headScripts = [];
        return $this;
    }

    public function clearFootScripts() {
        $this->_footScripts = [];
        return $this;
    }

// Content
    public function getContentType() {
        return 'text/html; charset=utf-8';
    }

    protected function _normalizeSlotContent($content) {
        return $this->html->string($content);
    }

// Notification
    public function toNotification($to=null, $from=null) {
        $content = $this->render();
        $subject = $this->getTitle();

        if(empty($subject)) {
            $subject = $this->_('Notification from %a%', ['%a%' => $this->context->application->getName()]);
        }

        $manager = flow\Manager::getInstance();
        return $manager->newNotification($subject, $content, $to, $from)
            ->setBodyType(flow\INotification::HTML);
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
        if(!$this->context->request->isArea('front')) {
            $this->canIndex(false)->canFollow(false);
        }

        if($this->context->application->getRunMode() == 'Http') {
            if($this->context->http->isAjaxRequest()) {
                $this->_shouldRenderBase = false;
            }
        }

        parent::_beforeRender();
    }

    public function render() {
        $output = parent::render();

        if(empty($this->_title) && empty($this->_titlePrefix) && empty($this->_titleSuffix)) {
            $this->setTitle(static::DEFAULT_TITLE);
        }

        if($this->_shouldRenderBase) {
            $output =
                '<!DOCTYPE html>'."\n".
                $this->htmlTag->open()."\n".
                $this->_renderHead()."\n".
                $this->bodyTag->open()."\n".
                $output."\n".
                $this->_renderFoot().
                $this->bodyTag->close()."\n".
                $this->htmlTag->close();
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

        // Links
        $fav = null;

        foreach($this->_links as $key => $link) {
            if($key == 'favicon') {
                $fav = $link;
                continue;
            }

            $output .= '    '.$link."\n";
        }

        if($fav) {
            $output .= '    '.$fav."\n";
        }

        // Meta
        foreach($meta as $key => $value) {
            if($value !== null) {
                $output .= '    '.$this->_metaToString($key, $value)."\n";
            }
        }

        if(!empty($this->_data)) {
            $attr = [];

            foreach($this->_data as $key => $value) {
                $attr[] = 'data-'.$key.'="'.$this->esc($value).'"';
            }

            $output .= '    <meta id="custom-view-data" '.implode(' ', $attr).' />'."\n";
        }

        // Css
        uasort($this->_css, [$this, '_sortEntries']);
        $output .= $this->_renderCssList();

        // Style
        if($this->_styles) {
            $output .= '    '.str_replace("\n", "\n    ", $this->_styles->toString())."\n";
        }

        // Js
        uasort($this->_js, [$this, '_sortEntries']);
        $output .= $this->_renderJsList('head');

        // Scripts
        $output .= $this->_renderScriptList($this->_headScripts);

        $output .= '</head>'."\n";
        return $output;
    }

    protected function _renderFoot() {
        return $this->_renderJsList('foot').
                $this->_renderScriptList($this->_footScripts);
    }

    protected function _sortEntries($a, $b) {
        $a = $a['weight'];
        $b = $b['weight'];

        if($a == $b) {
            return 0;
        }

        return ($a < $b) ? -1 : 1;
    }

    protected function _renderCssList() {
        if(empty($this->_css)) {
            return null;
        }

        $output = '';

        foreach($this->_css as $entry) {
            $attributes = array_merge(
                isset($entry['attributes']) ? $entry['attributes'] : [],
                [
                    'href' => $entry['url'],
                    'rel' => 'stylesheet',
                    'type' => 'text/css'
                ]
            );

            $tag = new aura\html\Tag('link', $attributes);
            $line = '    '.$tag->__toString()."\n";

            if(isset($entry['condition'])) {
                $line = $this->_addCondition($line, $entry['condition']);
            }

            $output .= $line;
        }

        return $output;
    }


    protected function _renderJsList($location) {
        if(empty($this->_js)) {
            return null;
        }

        $output = '';

        foreach($this->_js as $url => $entry) {
            if($entry['location'] != $location) {
                continue;
            }

            $attributes = array_merge(
                isset($entry['attributes']) ? $entry['attributes'] : [],
                [
                    'src' => $entry['url'],
                    'type' => 'text/javascript'
                ]
            );

            $tag = new aura\html\Tag('script', $attributes);
            $line = '    '.$tag->open().$tag->close()."\n";

            if(isset($entry['condition'])) {
                $line = $this->_addCondition($line, $entry['condition']);
            }

            $output .= $line;
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
        } elseif(strpos($key, ':') !== false) {
            return '<meta property="'.$this->esc($key).'" content="'.$this->esc($value).'" />';
        } else {
            return '<meta name="'.$this->esc($key).'" content="'.$this->esc($value).'" />';
        }
    }



// Dump
    public function getDumpProperties() {
        $output = [
            'title' => $this->getFullTitle()
        ];

        if($this->_baseHref) {
            $output['baseHref'] = $this->_baseHref;
        }

        if($this->_headers) {
            $output['headers'] = $this->_headers;
        }

        if($this->_cookies) {
            $output['cookies'] = $this->_cookies;
        }

        if($this->_useLayout) {
            $output['layout'] = $this->_layout;
        } else {
            $output['layout'] = false;
        }

        $output['theme'] = $this->_theme;

        $output['meta'] = $this->_meta;
        $output['data'] = $this->_data;

        if(!empty($this->_css)) {
            $output['css'] = $this->_css;
        }

        if($this->_styles) {
            $output['styles'] = $this->_styles;
        }

        if(!empty($this->_js)) {
            $output['js'] = $this->_js;
        }

        if($this->_headScripts) {
            $output['headScripts'] = $this->_headScripts;
        }

        if($this->_footScripts) {
            $output['footScripts'] = $this->_footScripts;
        }

        if($this->_links) {
            $output['links'] = $this->_links;
        }

        $output['htmlTag'] = $this->htmlTag;
        $output['bodyTag'] = $this->bodyTag;
        $output['renderBase'] = $this->_shouldRenderBase;
        $output['content'] = $this->content;
        $output['slots'] = $this->slots;

        return $output;
    }
}
