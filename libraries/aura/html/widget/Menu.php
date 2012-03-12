<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use df;
use df\core;
use df\aura;

class Menu extends Base implements core\IDumpable {
    
    const PRIMARY_TAG = 'nav';
    
    protected $_items;
    
    public function __construct($input=null) {
        $this->_items = new aura\html\ElementContent(); 
        
        if($input !== null) {
            core\stub($input);
        }
    }
    
    
    protected function _render() {
        $tag = $this->getTag();
        $ul = new aura\html\Tag('ul');
        $content = new aura\html\ElementContent();
        $renderTarget = $this->getRenderTarget();
        
        
        foreach($this->_items as $item) {
            if($item instanceof aura\view\IDeferredRenderable) {
                $item->setRenderTarget($renderTarget);
            }
            
            $entry = new aura\html\Element('li', $item);
            $content->push($entry);
        }
        
        return $tag->renderWith($ul->renderWith($content), true);
    }
    
    public function setItems(array $links) {
        $this->_items->clear();
        
        foreach($links as $link) {
            if($link instanceof ILinkWidget) {
                $this->addLink($link);
            } else if($link instanceof self) {
                $this->addMenu($link);
            } else if($this->_items->getLast() instanceof ILinkWidget) {
                $this->addSpacer();
            }
        }
        
        return $this;
    }
    
    public function addLink($link) {
        if(!$link instanceof ILinkWidget) {
            $link = Base::factory('Link', func_get_args())->setRenderTarget($this->_renderTarget);
        }
        
        $this->_items->push($link);
        return $this;
    }
    
    public function addMenu(self $menu) {
        $this->_items->push($menu);
        return $this;
    }
    
    public function addSpacer() {
        $this->_items->push(new aura\html\ElementString('<span class="widget spacer">|</span>'));
        return $this;
    }
    
    public function getItems() {
        return $this->_items;
    }
    
    public function removeItem($index) {
        $this->_items->remove($index);
        return $this;
    }
    
    public function clearItems() {
        $this->_items->clear();
        return $this;
    }
    
    
// Dump
    public function getDumpProperties() {
        return [
            'items' => $this->_items,
            'tag' => $this->getTag(),
            'renderTarget' => $this->_getRenderTargetDisplayName()
        ];
    }
}
