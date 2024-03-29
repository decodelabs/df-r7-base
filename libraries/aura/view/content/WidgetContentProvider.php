<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\aura\view\content;

use DecodeLabs\Glitch\Dumpable;
use df\arch;
use df\aura;

use df\core;

class WidgetContentProvider extends aura\html\Element implements
    aura\view\ICollapsibleContentProvider,
    aura\html\widget\IWidgetShortcutProvider,
    aura\html\IMarkupAdder,
    Dumpable
{
    use core\TContextAware;
    use aura\view\TView_DeferredRenderable;

    protected $_name = 'section';
    protected $_wrap = true;

    public function __construct(arch\IContext $context)
    {
        $theme = aura\theme\Base::factory($context);

        parent::__construct($theme->getDefaultContentContainerName());
        $this->context = $context;
    }

    public function collapse()
    {
        $output = aura\html\ElementContent::normalize($this->_collection);
        $this->clear();
        $this->push($output);

        return $this;
    }

    public function shouldWrap(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_wrap = $flag;
            return $this;
        }

        return $this->_wrap;
    }


    // Renderable
    public function getView()
    {
        return $this->getRenderTarget()->getView();
    }

    public function toResponse()
    {
        return $this->getView();
    }

    public function render()
    {
        if ($this->_wrap) {
            return parent::render();
        } else {
            return aura\html\ElementContent::normalize($this->_collection);
        }
    }


    // Widget shortcuts
    public function __call($method, array $args)
    {
        if (substr($method, 0, 3) == 'add') {
            $method = lcfirst(substr($method, 3));

            if (empty($method)) {
                $method = '__invoke';
            }

            $widget = $this->context->html->{$method}(...$args);

            if ($widget instanceof aura\view\IDeferredRenderable) {
                $widget->setRenderTarget($this->_renderTarget);
            }

            $this->push($widget);
            return $widget;
        }
    }


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'values' => $this->_collection;
    }
}
