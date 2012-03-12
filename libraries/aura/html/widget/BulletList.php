<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use df;
use df\core;
use df\aura;

class BulletList extends Base implements ILinearListWidget, IDataDrivenListWidget, core\IDumpable {
    
    use TDataDrivenListWidget;
    use TLinearListWidget;
    
    const PRIMARY_TAG = 'ul';
    
    public function __construct($data, Callable $renderer=null) {
        $this->setData($data);
        $this->setRenderer($renderer);
    }
    
    protected function _render() {
        $tag = $this->getTag();
        $children = new aura\html\ElementContent();
        
        $data = $this->_data;
        
        if(!$this->_isDataIterable()) {
            $data = array($data);
        }
        
        if(empty($data)) {
            return '';
        }
        
        $renderContext = new aura\html\widget\util\RendererContext($this);
        $renderTarget = $this->getRenderTarget();
        
        foreach($data as $key => $value) {
            $liTag = new aura\html\Tag('li');
            $renderContext->iterate($key, $liTag);
            
            $value = $this->_renderListItem($renderContext, $value);
            
            if($value === null) {
                continue;
            }
            
            $children->push($liTag->renderWith($value));
        }
        
        if($children->isEmpty()) {
            return '';
        }
        
        return $tag->renderWith($children, true);
    }
    
// Dump
    public function getDumpProperties() {
        return [
            'data' => count($this->_data).' rows',
            'renderer' => $this->_renderer,
            'tag' => $this->getTag(),
            'renderTarget' => $this->_getRenderTargetDisplayName()
        ];
    }
}
