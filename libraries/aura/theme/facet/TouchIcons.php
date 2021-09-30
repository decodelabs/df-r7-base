<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\aura\theme\facet;

use df;
use df\core;
use df\aura;

class TouchIcons extends Base
{
    public function afterHtmlViewRender(aura\view\IHtmlView $view)
    {
        if ($view->context->location->isArea('front')) {
            $theme = $view->getTheme();
        } else {
            $theme = aura\theme\Base::factory('~front');
        }

        if (!$theme->getApplicationImagePath()) {
            return;
        }

        $current = $view->getTheme()->getId();

        $view
            // Chrome android
            ->addLink('touch-chrome-android', 'icon', 'touch-icon-192x192.png?theme='.$current.'&cts', ['sizes' => '192x192'])
            // iPhone 6 plus (3x)
            ->addLink('touch-apple-180', 'apple-touch-icon', 'apple-touch-icon-180x180.png?theme='.$current.'&cts', ['sizes' => '180x180'])
            // iPad IOS 7+ (2x)
            ->addLink('touch-apple-152', 'apple-touch-icon', 'apple-touch-icon-152x152.png?theme='.$current.'&cts', ['sizes' => '152x152'])
            // iPad IOS <6 (2x)
            ->addLink('touch-apple-144', 'apple-touch-icon', 'apple-touch-icon-144x144.png?theme='.$current.'&cts', ['sizes' => '144x144'])
            // iPhone IOS 7+ (2x)
            ->addLink('touch-apple-120', 'apple-touch-icon', 'apple-touch-icon-120x120.png?theme='.$current.'&cts', ['sizes' => '120x120'])
            // iPhone IOS <6 (2x)
            ->addLink('touch-apple-114', 'apple-touch-icon', 'apple-touch-icon-114x114.png?theme='.$current.'&cts', ['sizes' => '114x114'])
            // iPad mini IOS 7+ (1x)
            ->addLink('touch-apple-76', 'apple-touch-icon', 'apple-touch-icon-76x76.png?theme='.$current.'&cts', ['sizes' => '76x76'])
            // iPad mini IOS <6 (1x)
            ->addLink('touch-apple-72', 'apple-touch-icon', 'apple-touch-icon-72x72.png?theme='.$current.'&cts', ['sizes' => '72x72'])
            // Non retina apple, android 2.1+
            ->addLink('touch-sd', 'apple-touch-icon', 'apple-touch-icon-57x57.png?theme='.$current.'&cts')
            // IE
            ->setMeta('msapplication-TileImage', $view->uri('touch-icon-150x150.png?theme='.$current.'&cts'));
    }
}
