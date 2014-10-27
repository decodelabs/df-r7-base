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

class WidgetContentProvider extends aura\html\ElementContent implements aura\view\IContentProvider, aura\html\widget\IWidgetShortcutProvider {
    
    use core\TContextAware;
    use core\TArgContainer;
    use aura\view\TDeferredRenderable;
    
    public function __construct(arch\IContext $context) {
        $this->context = $context;
    }
    
    
// Renderable
    public function getView() {
        return $this->getRenderTarget()->getView();
    }
    
    public function toResponse() {
        return $this->getView();
    }
    
    
// Widget shortcuts
    public function __call($method, array $args) {
        if(substr($method, 0, 3) == 'add') {
            $widget = aura\html\widget\Base::factory($this->context, substr($method, 3), $args)
                ->setRenderTarget($this->_renderTarget);
                
            $this->push($widget);
            return $widget;
        }
    }
}