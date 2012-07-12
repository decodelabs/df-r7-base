<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use df;
use df\core;
use df\aura;
use df\arch;

class Menu extends Base implements core\IDumpable {
    
    const PRIMARY_TAG = 'nav';
    
    protected $_entries;
    protected $_renderIfEmpty = false;
    protected $_context;
    
    public function __construct(arch\IContext $context, $input=null) {
        $this->_entries = new aura\html\ElementContent(); 
        $this->_context = $context;

        if(is_string($input) || $input instanceof core\uri\IUrl) {
            $menu = arch\menu\Base::factory($this->_context, $input);
        }

        if($input !== null) {
            $this->addEntries($input);
        }
    }
    
    
    protected function _render() {
        $tag = $this->getTag()->shouldRenderIfEmpty($this->_renderIfEmpty);
        
        $content = new aura\html\ElementContent();
        $renderTarget = $this->getRenderTarget();
        
        foreach($this->_entries as $entry) {
            if($entry instanceof aura\view\IDeferredRenderable) {
                $entry->setRenderTarget($renderTarget);
            }
            
            $entry = new aura\html\Element('li', $entry);
            $entry->shouldRenderIfEmpty(false);
            $content->push($entry);
        }

        return $tag->renderWith(
            (new aura\html\Tag('ul'))->shouldRenderIfEmpty($this->_renderIfEmpty)->renderWith($content), 
            true
        );
    }
    
    public function setEntries($entries) {
        $this->_entries->clear();
        return call_user_func_array([$this, 'addEntries'], func_get_args());
    }

    public function addLinks($entries) {
        return call_user_func_array([$this, 'addEntries'], func_get_args());
    }

    public function addEntries($entries) {
        if($entries instanceof arch\menu\IMenu) {
            $entries = $entries->generateEntries()->toArray();
        }

        if(!is_array($entries)) {
            $entries = func_get_args();
        }
        
        foreach($entries as $entry) {
            if($entry instanceof ILinkWidget
            || $entry instanceof arch\menu\entry\Link) {
                $this->addLink($entry);
            } else if($entry instanceof self
            || $entry instanceof arch\menu\entry\Submenu) {
                $this->addMenu($entry);
            } else if($entry instanceof arch\menu\entry\spacer
            || $this->_entries->getLast() instanceof ILinkWidget) {
                $this->addSpacer();
            }
        }
        
        return $this;
    }
    
    public function addLink($link) {
        if(!$link instanceof ILinkWidget) {
            $link = Base::factory($this->_context, 'Link', func_get_args())->setRenderTarget($this->_renderTarget);
        }
        
        $this->_entries->push($link);
        return $this;
    }
    
    public function addMenu(self $menu) {
        $this->_entries->push($menu);
        return $this;
    }
    
    public function addSpacer() {
        $this->_entries->push(new aura\html\ElementString('<span class="widget spacer">|</span>'));
        return $this;
    }
    
    public function getEntries() {
        return $this->_entries;
    }
    
    public function removeEntry($index) {
        $this->_entries->remove($index);
        return $this;
    }
    
    public function clearEntries() {
        $this->_entries->clear();
        return $this;
    }

    public function shouldRenderIfEmpty($flag=null) {
        if($flag !== null) {
            $this->_renderIfEmpty = (bool)$flag;
            return $this;
        }

        return $this->_renderIfEmpty;
    }
    
    
// Dump
    public function getDumpProperties() {
        return [
            'entries' => $this->_entries,
            'tag' => $this->getTag(),
            'renderTarget' => $this->_getRenderTargetDisplayName()
        ];
    }
}
