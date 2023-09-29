<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\aura\view;

use DecodeLabs\Elementary\Style\Sheet as StyleSheet;
use DecodeLabs\Genesis;

use DecodeLabs\Glitch\Dumpable;
use DecodeLabs\R7\Legacy;
use DecodeLabs\Tagged;
use df\arch;

class Html extends Base implements IHtmlView, Dumpable
{
    use TView_Response;
    use TView_Layout;

    public const DEFAULT_TITLE = '*untitled';
    public const DEFAULT_LAYOUT = 'Default';

    public const META_PRIORITY = ['x-ua-compatible', 'content-type'];
    public const META_HTTP = [
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

    public function __construct($type, arch\IContext $context)
    {
        parent::__construct($type, $context);

        $this->htmlTag = Tagged::tag('html', ['lang' => 'en']);
        $this->bodyTag = Tagged::tag('body');

        //$this->_baseHref = $this->uri->__invoke('/');

        $this
            ->setMeta('X-UA-Compatible', 'IE=edge,chrome=1')
            ->setMeta('content-type', $this->getContentType())
            ->setMeta('viewport', 'width=device-width, minimum-scale=0.5, maximum-scale=1.5')
        ;

        $this->getHeaders()
            ->set('X-Frame-Options', 'SAMEORIGIN')
            ->set('X-XSS-Protection', '1; mode=block')
            ->set('X-Content-Type-Options', 'nosniff')
            ->set('Referrer-Policy', 'no-referrer-when-downgrade')
        ;
    }


    // Tags
    public function getHtmlTag()
    {
        return $this->htmlTag;
    }

    public function getBodyTag()
    {
        return $this->bodyTag;
    }


    // Language
    public function setLanguage(string $language)
    {
        $this->htmlTag->setAttribute('lang', $language);
        return $this;
    }

    public function getLanguage(): string
    {
        return $this->htmlTag->getAttribute('lang', 'en');
    }



    // Title
    public function setTitle(?string $title)
    {
        if (empty($title)) {
            $title = null;
        }

        $this->_title = $title;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->_title;
    }

    public function hasTitle(): bool
    {
        return $this->_title !== null;
    }

    public function setTitlePrefix($prefix)
    {
        $this->_titlePrefix = $prefix;
        return $this;
    }

    public function getTitlePrefix()
    {
        return $this->_titlePrefix;
    }

    public function hasTitlePrefix(): bool
    {
        return $this->_titlePrefix !== null;
    }

    public function setTitleSuffix($suffix)
    {
        $this->_titleSuffix = $suffix;
        return $this;
    }

    public function getTitleSuffix()
    {
        return $this->_titleSuffix;
    }

    public function hasTitleSuffix(): bool
    {
        return $this->_titleSuffix !== null;
    }

    public function setFullTitle($title)
    {
        return $this->setTitle($title)->setTitlePrefix(false)->setTitleSuffix(false);
    }

    public function getFullTitle()
    {
        return $this->_titlePrefix . $this->_title . $this->_titleSuffix;
    }


    // Base
    public function setBaseHref($url)
    {
        $this->_baseHref = $this->uri->__invoke($url);
        return $this;
    }

    public function getBaseHref()
    {
        return $this->_baseHref;
    }


    // Meta
    public function setMeta($key, $value)
    {
        if ($value === null) {
            unset($this->_meta[$key]);
        } else {
            if (!is_array($value)) {
                $value = (string)$value;
            }

            $this->_meta[$key] = $value;
        }

        return $this;
    }

    public function getMeta($key)
    {
        if (isset($this->_meta[$key])) {
            return $this->_meta[$key];
        }

        return null;
    }

    public function hasMeta($key)
    {
        return isset($this->_meta[$key]);
    }

    public function removeMeta($key)
    {
        unset($this->_meta[$key]);
        return $this;
    }


    // Data
    public function setData($key, $value)
    {
        if ($value === null) {
            unset($this->_data[$key]);
        } else {
            $this->_data[$key] = (string)$value;
        }

        return $this;
    }

    public function getData($key)
    {
        if (isset($this->_data[$key])) {
            return $this->_data[$key];
        }

        return null;
    }

    public function hasData($key)
    {
        return isset($this->_data[$key]);
    }

    public function removeData($key)
    {
        unset($this->_data[$key]);
        return $this;
    }


    // Robots
    public function canIndex(bool $flag = null, $bot = 'robots')
    {
        if ($this->hasMeta($bot)) {
            $current = explode(',', $this->getMeta($bot));
        } else {
            $current = [];
        }

        $current = array_flip($current);

        if ($flag !== null) {
            if (!$flag) {
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

    public function canFollow(bool $flag = null, $bot = 'robots')
    {
        if ($this->hasMeta($bot)) {
            $current = explode(',', $this->getMeta($bot));
        } else {
            $current = [];
        }

        $current = array_flip($current);

        if ($flag !== null) {
            if (!$flag) {
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

    public function setRobots($value)
    {
        return $this->setMeta('robots', $value);
    }

    public function getRobots()
    {
        return $this->getMeta('robots');
    }

    public function hasRobots()
    {
        return $this->hasMeta('robots');
    }

    public function removeRobots()
    {
        return $this->removeMeta('robots');
    }


    // Link
    public function addLink($id, $rel, $url, array $attr = null)
    {
        $attributes = [
            'rel' => $rel,
            'href' => $this->uri($url)
        ];

        if ($attr) {
            $attributes = array_merge($attributes, $attr);
        }

        $this->_links[$id] = Tagged::el('link', null, $attributes);
        return $this;
    }

    public function getLinks()
    {
        return $this->_links;
    }

    public function getLink($id)
    {
        if (isset($this->_links[$id])) {
            return $this->_links[$id];
        }
    }

    public function removeLink($id)
    {
        unset($this->_links[$id]);
        return $this;
    }

    public function clearLinks()
    {
        $this->_links = [];
        return $this;
    }


    // Canonical
    public function setCanonical($canonical)
    {
        $url = $this->uri($canonical);

        if (!isset($this->_links['canonical'])) {
            $this->addLink('canonical', 'canonical', $url);
        } else {
            $this->_links['canonical']->setAttribute('href', $url);
        }

        return $this;
    }

    public function getCanonical()
    {
        if (isset($this->_links['canonical'])) {
            return $this->_links['canonical']->getAttribute('href');
        }
    }


    // Favicon
    public function setFaviconHref($url)
    {
        $url = $this->uri($url);
        $url->query->favicon = null;

        if (!isset($this->_links['favicon'])) {
            $this->addLink('favicon', 'shortcut icon', $url);
        } else {
            $this->_links['favicon']->setAttribute('href', $url);
        }

        return $this;
    }

    public function getFaviconHref()
    {
        if (isset($this->_links['favicon'])) {
            return $this->_links['favicon']->getAttribute('href');
        }
    }

    public function linkFavicon($url)
    {
        return $this->setFaviconHref($url);
    }



    // CSS
    public function linkCss($uri, $weight = null, array $attributes = null, $condition = null)
    {
        $url = $this->uri($uri);

        if ($weight === null) {
            $weight = ++$this->_cssMaxWeight;
        } elseif ($weight > $this->_cssMaxWeight) {
            $this->_cssMaxWeight = $weight;
        }

        $entry = [
            'weight' => $weight,
            'url' => $url
        ];

        if ($attributes !== null) {
            $entry['attributes'] = $attributes;
        }

        if ($condition !== null) {
            $entry['condition'] = $condition;
        }

        $url = (string)$url;

        if (isset($this->_css[$url])) {
            $this->_css[$url] = array_merge($this->_css[$url], $entry);
        } else {
            $this->_css[$url] = $entry;
        }

        return $this;
    }

    public function linkConditionalCss($condition, $uri, $weight = null, array $attributes = null)
    {
        return $this->linkCss($uri, $weight, $attributes, $condition);
    }

    public function getCss()
    {
        return $this->_css;
    }

    public function clearCss()
    {
        $this->_css = [];
        return $this;
    }


    // Styles
    public function setStyles(...$styles)
    {
        $this->getStyles()->clear()->import(...$styles);
        return $this;
    }

    public function addStyles(...$styles)
    {
        $this->getStyles()->import(...$styles);
        return $this;
    }

    public function getStyles()
    {
        if (!$this->_styles) {
            $this->_styles = new StyleSheet();
        }

        return $this->_styles;
    }

    public function hasStyles()
    {
        return $this->_styles !== null && !$this->_styles->isEmpty();
    }

    public function removeStyles()
    {
        $this->_styles = null;
        return $this;
    }

    public function setStyle($key, $value)
    {
        $this->getStyles()->set($key, $value);
        return $this;
    }

    public function getStyle($key)
    {
        if (!$this->_styles) {
            return null;
        }

        return $this->_styles->get($key);
    }

    public function removeStyle(...$keys)
    {
        if ($this->_styles) {
            $this->_styles->remove(...$keys);
        }

        return $this;
    }

    public function hasStyle(...$keys)
    {
        if (!$this->_styles) {
            return false;
        }

        return $this->_styles->has(...$keys);
    }



    // Js
    public function linkJs($uri, $weight = null, array $attributes = null, $condition = null)
    {
        return $this->_linkJs('head', $uri, $weight, $attributes, $condition);
    }

    public function linkConditionalJs($condition, $uri, $weight = null, array $attributes = null)
    {
        return $this->_linkJs('head', $uri, $weight, $attributes, $condition);
    }

    public function linkFootJs($uri, $weight = null, array $attributes = null, $condition = null)
    {
        return $this->_linkJs('foot', $uri, $weight, $attributes, $condition);
    }

    public function linkConditionalFootJs($condition, $uri, $weight = null, array $attributes = null)
    {
        return $this->_linkJs('foot', $uri, $weight, $attributes, $condition);
    }


    protected function _linkJs($location, $uri, $weight = null, array $attributes = null, $condition = null)
    {
        if (
            is_string($uri) &&
            (
                str_starts_with((string)$uri, 'http:') ||
                str_starts_with((string)$uri, 'https:')
            )
        ) {
            $url = $uri;
        } else {
            $url = $this->uri($uri);
        }

        if ($weight === null) {
            $weight = ++$this->_jsMaxWeight;
        } elseif ($weight > $this->_jsMaxWeight) {
            $this->_jsMaxWeight = $weight;
        }

        $entry = [
            'weight' => $weight,
            'url' => $url,
            'location' => $location
        ];

        if ($attributes !== null) {
            if (isset($attributes['__invoke'])) {
                $entry['invoke'] = $attributes['__invoke'];
                unset($attributes['__invoke']);
            }

            $entry['attributes'] = $attributes;
        }

        if ($condition !== null) {
            $entry['condition'] = $condition;
        }

        $url = (string)$url;

        if (isset($this->_js[$url])) {
            $this->_js[$url] = array_merge($this->_js[$url], $entry);
        } else {
            $this->_js[$url] = $entry;
        }

        return $this;
    }


    public function getJs()
    {
        return $this->_js;
    }

    public function getHeadJs()
    {
        $output = [];

        foreach ($this->_js as $url => $entry) {
            if ($entry['location'] != 'head') {
                continue;
            }

            $output[$url] = $entry;
        }

        return $output;
    }

    public function getFootJs()
    {
        $output = [];

        foreach ($this->_js as $url => $entry) {
            if ($entry['location'] != 'foot') {
                continue;
            }

            $output[$url] = $entry;
        }

        return $output;
    }


    public function clearJs()
    {
        $this->_js = [];
        return $this;
    }

    public function clearHeadJs()
    {
        foreach ($this->_js as $url => $entry) {
            if ($entry['location'] == 'head') {
                unset($this->_js[$url]);
            }
        }

        return $this;
    }

    public function clearFootJs()
    {
        foreach ($this->_js as $url => $entry) {
            if ($entry['location'] == 'foot') {
                unset($this->_js[$url]);
            }
        }

        return $this;
    }


    // Scripts
    public function addScript(
        string $id,
        string $script,
        ?array $attributes = null,
        ?string $noScript = null
    ) {
        return $this->addHeadScript($id, $script, $attributes, $noScript);
    }

    public function addHeadScript(
        string $id,
        string $script,
        ?array $attributes = null,
        ?string $noScript = null
    ) {
        $this->_headScripts[$id] = [
            'script' => $script,
            'attributes' => $attributes,
            'noScript' => $noScript
        ];

        return $this;
    }

    public function addFootScript(
        string $id,
        string $script,
        ?array $attributes = null,
        ?string $noScript = null
    ) {
        $this->_footScripts[$id] = [
            'script' => $script,
            'attributes' => $attributes,
            'noScript' => $noScript
        ];

        return $this;
    }

    public function getHeadScript(string $id)
    {
        return $this->_headScripts[$id] ?? null;
    }

    public function getFootScript(string $id)
    {
        return $this->_footScripts[$id] ?? null;
    }

    public function removeScript(string $id)
    {
        return $this->removeHeadScript($id)->removeFootScript($id);
    }

    public function removeHeadScript(string $id)
    {
        unset($this->_headScripts[$id]);
        return $this;
    }

    public function removeFootScript(string $id)
    {
        unset($this->_footScripts[$id]);
        return $this;
    }

    public function clearScripts()
    {
        return $this->clearHeadScripts()->clearFootScripts();
    }

    public function clearHeadScripts()
    {
        $this->_headScripts = [];
        return $this;
    }

    public function clearFootScripts()
    {
        $this->_footScripts = [];
        return $this;
    }

    // Content
    public function getContentType()
    {
        return 'text/html; charset=utf-8';
    }

    protected function _normalizeSlotContent($content)
    {
        return Tagged::raw($content);
    }

    // Rendering
    public function shouldRenderBase(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_shouldRenderBase = $flag;
            return $this;
        }

        return $this->_shouldRenderBase;
    }

    protected function _beforeRender()
    {
        if (
            $this->context->request->isArea('admin') ||
            $this->context->request->isArea('devtools') ||
            $this->context->request->isArea('mail')
        ) {
            $this->canIndex(false)->canFollow(false);
        }

        if (Genesis::$kernel->getMode() == 'Http') {
            if (Legacy::$http->isAjaxRequest()) {
                $this->_shouldRenderBase = false;
            }
        }

        parent::_beforeRender();
    }

    public function render()
    {
        $output = parent::render();

        if (empty($this->_title) && empty($this->_titlePrefix) && empty($this->_titleSuffix)) {
            $this->setTitle(static::DEFAULT_TITLE);
        }

        if ($this->_shouldRenderBase) {
            $output =
                '<!DOCTYPE html>' . "\n" .
                $this->htmlTag->open() . "\n" .
                $this->_renderHead() . "\n" .
                $this->bodyTag->open() . "\n" .
                $output . "\n" .
                $this->_renderFoot() .
                $this->bodyTag->close() . "\n" .
                $this->htmlTag->close();
        }

        return $output;
    }

    protected function _renderHead()
    {
        $output = '<head>' . "\n";
        $meta = $this->_meta;

        // Priority meta
        ksort($meta);

        foreach ($meta as $key => $value) {
            if (in_array(strtolower((string)$key), self::META_PRIORITY)) {
                $output .= '    ' . $this->_metaToString($key, $meta[$key]) . "\n";
                unset($meta[$key]);
            }
        }

        // Title
        $output .= '    <title>' . Tagged::esc($this->getFullTitle()) . '</title>' . "\n";

        // Base
        if ($this->_baseHref !== null) {
            $output .= '    <base href="' . Tagged::esc($this->_baseHref) . '" />' . "\n";
        }

        // Links
        $fav = null;

        foreach ($this->_links as $key => $link) {
            if ($key == 'favicon') {
                $fav = $link;
                continue;
            }

            $output .= '    ' . $link . "\n";
        }

        if ($fav) {
            $output .= '    ' . $fav . "\n";
        }

        // Meta
        foreach ($meta as $key => $value) {
            if ($value !== null) {
                $output .= '    ' . $this->_metaToString($key, $value) . "\n";
            }
        }

        if (!empty($this->_data)) {
            $attr = [];

            foreach ($this->_data as $key => $value) {
                $attr[] = 'data-' . $key . '="' . Tagged::esc($value) . '"';
            }

            $attr[] = 'data-' . Genesis::$environment->getMode();

            $output .= '    <meta id="custom-view-data" ' . implode(' ', $attr) . ' />' . "\n";
        }

        // Css
        uasort($this->_css, [$this, '_sortEntries']);
        $output .= $this->_renderCssList();

        // Style
        if ($this->_styles) {
            $output .= '    ' . str_replace("\n", "\n    ", (string)$this->_styles) . "\n";
        }

        // Js
        uasort($this->_js, [$this, '_sortEntries']);
        $output .= $this->_renderJsList('head');

        // Scripts
        $output .= $this->_renderScriptList($this->_headScripts);

        $output .= '</head>' . "\n";
        return $output;
    }

    protected function _renderFoot()
    {
        return $this->_renderJsList('foot') .
                $this->_renderScriptList($this->_footScripts);
    }

    protected function _sortEntries($a, $b)
    {
        return $a['weight'] <=> $b['weight'];
    }

    protected function _renderCssList()
    {
        if (empty($this->_css)) {
            return null;
        }

        $output = '';

        foreach ($this->_css as $entry) {
            $attributes = array_merge(
                $entry['attributes'] ?? [],
                [
                    'href' => $entry['url'],
                    'rel' => 'stylesheet',
                    'type' => 'text/css'
                ]
            );

            $tag = Tagged::tag('link', $attributes);
            $line = '    ' . $tag->__toString() . "\n";

            if (isset($entry['condition'])) {
                $line = $this->_addCondition($line, $entry['condition']);
            }

            $output .= $line;
        }

        return $output;
    }


    protected function _renderJsList($location)
    {
        if (empty($this->_js)) {
            return null;
        }

        $output = '';
        $nonce = null;

        if ($csp = $this->context->app->getCsp('text/html')) {
            $nonce = $csp->getNonce();
        }

        foreach ($this->_js as $url => $entry) {
            if ($entry['location'] != $location) {
                continue;
            }

            $attributes = array_merge(
                [
                    'src' => $entry['url'],
                    'type' => 'text/javascript'
                ],
                $entry['attributes'] ?? []
            );

            if ($nonce !== null) {
                $attributes['nonce'] = $nonce;
            }

            $tag = Tagged::tag('script', $attributes);
            $line = '    ' . $tag->open() . $tag->close() . "\n";

            if (isset($entry['condition'])) {
                $line = $this->_addCondition($line, $entry['condition']);
            }

            $output .= $line;

            if (isset($entry['invoke'])) {
                $attributes = [
                    'type' => 'text/javascript'
                ];

                if ($nonce !== null) {
                    $attributes['nonce'] = $nonce;
                }

                $tag = Tagged::tag('script', $attributes);
                $output .= '    ' . $tag->open() . $entry['invoke'] . $tag->close() . "\n";
            }
        }

        return $output;
    }

    protected function _renderScriptList(array $scripts)
    {
        if (empty($scripts)) {
            return null;
        }

        $output = '';
        $nonce = null;

        if ($csp = $this->context->app->getCsp('text/html')) {
            $nonce = $csp->getNonce();
        }

        foreach ($scripts as $id => $entry) {
            $attributes = $entry['attributes'] ?? [];
            $attributes['id'] = 'script-' . $id;
            $attributes['nonce'] = $nonce;

            if (!isset($attributes['type'])) {
                $attributes['type'] = 'text/javascript';
            }

            $line = '    ' . Tagged::tag('script', $attributes) .
                    "\n        " . str_replace("\n", "\n        ", $entry['script']) . "\n" .
                    '    </script>' . "\n";

            if (isset($entry['noScript'])) {
                $line .= '    <noscript>' . "\n" .
                        '        ' . str_replace("\n", "\n        ", $entry['noScript']) . "\n" .
                        '    </noscript>' . "\n";
            }

            if (isset($entry['condition'])) {
                $line = $this->_addCondition($line, $entry['condition']);
            }

            $output .= $line;
        }

        return $output;
    }

    protected function _addCondition($line, $condition)
    {
        return '    <!--[if ' . $condition . ' ]>' . trim($line) . '<![endif]-->' . "\n";
    }

    protected function _metaToString($key, $value)
    {
        if (in_array(strtolower((string)$key), self::META_HTTP)) {
            $nameKey = 'http-equiv';
        } elseif (strpos($key, 'og:') === 0) {
            $nameKey = 'property';
        } else {
            $nameKey = 'name';
        }

        $output = Tagged::tag('meta', [$nameKey => $key]);

        if (is_array($value)) {
            $output->setAttributes($value);
        } else {
            $output->setAttribute('content', $value);
        }

        return (string)$output;
    }


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'property:*title' => $this->getFullTitle();

        if ($this->_baseHref) {
            yield 'property:*baseHref' => $this->_baseHref;
        }

        if ($this->_headers) {
            yield 'property:*headers' => $this->_headers;
        }

        if ($this->_cookies) {
            yield 'property:*cookies' => $this->_cookies;
        }

        if ($this->_useLayout) {
            yield 'property:*layout' => $this->_layout;
        } else {
            yield 'property:*layout' => false;
        }

        yield 'property:*theme' => $this->_theme;

        yield 'property:*meta' => $this->_meta;
        yield 'property:*data' => $this->_data;

        if (!empty($this->_css)) {
            yield 'property:*css' => $this->_css;
        }

        if ($this->_styles) {
            yield 'property:*styles' => $this->_styles;
        }

        if (!empty($this->_js)) {
            yield 'property:*js' => $this->_js;
        }

        if ($this->_headScripts) {
            yield 'property:*headScripts' => $this->_headScripts;
        }

        if ($this->_footScripts) {
            yield 'property:*footScripts' => $this->_footScripts;
        }

        if ($this->_links) {
            yield 'property:*links' => $this->_links;
        }

        yield 'property:htmlTag' => $this->htmlTag;
        yield 'property:bodyTag' => $this->bodyTag;
        yield 'property:*renderBase' => $this->_shouldRenderBase;
        yield 'property:content' => $this->content;
        yield 'property:slots' => $this->slots;
        yield 'property:*ajax' => $this->_ajax;
    }
}
