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

class BulletList extends Base implements ILinearListWidget, IDataDrivenListWidget, core\IDumpable {
    
    use TWidget_DataDrivenList;
    use TWidget_RendererProvider;
    
    const PRIMARY_TAG = 'ul';
    
    public function __construct(arch\IContext $context, $data, Callable $renderer=null) {
        $this->setData($data);
        $this->setRenderer($renderer);
    }
    
    protected function _render() {
        $tag = $this->getTag();
        $children = new aura\html\ElementContent();
        
        $data = $this->_data;
        
        if(!$this->_isDataIterable() && $data !== null) {
            $data = array($data);
        }
        
        if(empty($data)) {
            return '';
        }
        
        $renderContext = new aura\html\widget\util\RendererContext($this);
        $renderContext->shouldConvertNullToNa(false);

        foreach($data as $key => $value) {
            $liTag = new aura\html\Tag('li');
            $renderContext->iterate($key, $liTag);
            $value = $renderContext->renderCell($value, $this->_renderer);

            if($value === null || $renderContext->shouldSkipRow()) {
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
