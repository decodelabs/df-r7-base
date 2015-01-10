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

class CommaList extends Base implements ILinearListWidget, IDataDrivenListWidget, core\IDumpable {
    
    use TWidget_DataDrivenList;
    use TWidget_RendererProvider;
    
    const PRIMARY_TAG = 'span';
    
    public function __construct(arch\IContext $context, $data, $renderer=null) {
        $this->setData($data);
        $this->setRenderer($renderer);
    }
    
    protected function _render() {
        $tag = $this->getTag();
        $children = new aura\html\ElementContent();
        
        $data = $this->_data;
        
        if(!$this->_isDataIterable() && $data !== null) {
            $data = [$data];
        }

        if(empty($data)) {
            return '';
        }
        
        $renderContext = new aura\html\widget\util\RendererContext($this);
        $renderContext->shouldConvertNullToNa(false);
        $first = true;

        foreach($data as $key => $value) {
            $cellTag = new aura\html\Tag('span');
            $renderContext->iterate($key, $cellTag);
            $value = $renderContext->renderCell($value, $this->_renderer);

            if($value === null || $renderContext->shouldSkipRow()) {
                continue;
            }

            if(!$first) {
                $children->push(', ');
            }

            $first = false;
            $children->push($cellTag->renderWith($value));
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
