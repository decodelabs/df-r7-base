<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\view\content;

use df;
use df\core;
use df\aura;
use df\arch;

class WidgetContentProvider extends aura\html\ElementContent implements aura\view\ICollapsibleContentProvider, aura\html\widget\IWidgetShortcutProvider {

    use core\TContextAware;
    use aura\view\TView_DeferredRenderable;

    protected $_wrapperTag = 'section';

    public function __construct(arch\IContext $context) {
        $this->context = $context;
    }

    public function wrapWith(?string $tag) {
        $this->_wrapperTag = $tag;
        return $this;
    }

    public function getWrapperTag(): ?string {
        return $this->_wrapperTag;
    }

    public function collapse() {
        $output = $this->render();
        $this->clear();
        $this->push($output);

        return $this;
    }


// Renderable
    public function getView() {
        return $this->getRenderTarget()->getView();
    }

    public function toResponse() {
        return $this->getView();
    }

    public function render() {
        $output = parent::render();

        if($this->_wrapperTag !== null) {
            $output = $this->context->html($this->_wrapperTag, $output);
        }

        return $output;
    }


// Widget shortcuts
    public function __call($method, array $args) {
        if(substr($method, 0, 3) == 'add') {
            $method = lcfirst(substr($method, 3));

            if(empty($method)) {
                $method = '__invoke';
            }

            $widget = $this->context->html->{$method}(...$args);

            if($widget instanceof aura\view\IDeferredRenderable) {
                $widget->setRenderTarget($this->_renderTarget);
            }

            $this->push($widget);
            return $widget;
        }
    }
}
