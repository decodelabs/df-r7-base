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
    
    use arch\TContextAware;
    use aura\view\TArgContainer;
    
    protected $_renderTarget;
    
    public function __construct(arch\IContext $context) {
        $this->_context = $context;
    }
    
    
// Renderable
    public function getView() {
        if(!$this->_renderTarget) {
            throw new aura\view\RuntimeException(
                'This view is not currently rendering'
            );
        }
        
        return $this->_renderTarget->getView();
    }
    
    public function renderTo(aura\view\IRenderTarget $target) {
        $this->_renderTarget = $target;
        $output = '';
        
        foreach($this->_values as $value) {
            if($value instanceof aura\html\widget\IWidget) {
                $output .= $value->renderTo($target);
            } else {
                $output .= $this->_renderChild($value);
            } 
        }
        
        return $output;
    }
    
    public function setRenderTarget(aura\view\IRenderTarget $target=null) {
        $this->_renderTarget = $target;
        return $this;
    }
    
    public function getRenderTarget() {
        return $this->_renderTarget;
    }
    
    
// Widget shortcuts
    public function __call($method, array $args) {
        if(substr($method, 0, 3) == 'add') {
            $widget = aura\html\widget\Base::factory(substr($method, 3), $args)
                ->setRenderTarget($this->_renderTarget);
                
            $this->push($widget);
            return $widget;
        }
    }
}