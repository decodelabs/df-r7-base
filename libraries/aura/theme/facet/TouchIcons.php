<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\theme\facet;

use df;
use df\core;
use df\aura;

class TouchIcons extends Base {
    
    public function renderToHtml(aura\view\IHtmlView $view) {
        $view
            // Chrome android
            ->addLink('touch-chrome-android', 'icon', 'touch-icon-192x192.png?cts', ['sizes' => '192x192'])
            // iPhone 6 plus (3x)
            ->addLink('touch-apple-180', 'apple-touch-icon-precomposed', 'apple-touch-icon-180x180-precomposed.png?cts', ['sizes' => '180x180'])
            // iPad IOS 7+ (2x)
            ->addLink('touch-apple-152', 'apple-touch-icon-precomposed', 'apple-touch-icon-152x152-precomposed.png?cts', ['sizes' => '152x152'])
            // iPad IOS <6 (2x)
            ->addLink('touch-apple-144', 'apple-touch-icon-precomposed', 'apple-touch-icon-144x144-precomposed.png?cts', ['sizes' => '144x144'])
            // iPhone IOS 7+ (2x)
            ->addLink('touch-apple-120', 'apple-touch-icon-precomposed', 'apple-touch-icon-120x120-precomposed.png?cts', ['sizes' => '120x120'])
            // iPhone IOS <6 (2x)
            ->addLink('touch-apple-114', 'apple-touch-icon-precomposed', 'apple-touch-icon-114x114-precomposed.png?cts', ['sizes' => '114x114'])
            // iPad mini IOS 7+ (1x)
            ->addLink('touch-apple-76', 'apple-touch-icon-precomposed', 'apple-touch-icon-76x76-precomposed.png?cts', ['sizes' => '76x76'])
            // iPad mini IOS <6 (1x)
            ->addLink('touch-apple-72', 'apple-touch-icon-precomposed', 'apple-touch-icon-72x72-precomposed.png?cts', ['sizes' => '72x72'])
            // Non retina apple, android 2.1+
            ->addLink('touch-sd', 'apple-touch-icon-precomposed', 'apple-touch-icon-57x57-precomposed.png?cts')
            // IE
            ->setMeta('msapplication-TileImage', $view->uri('touch-icon-150x150.png?cts'));
    }
}