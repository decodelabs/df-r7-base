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
    
class ArticleList extends BulletList implements IOrderedDataDrivenListWidget {

    use TWidget_OrderedDataDrivenList;

    const PRIMARY_TAG = 'ol';

    protected function _render() {
        $tag = $this->getTag();
        $children = new aura\html\ElementContent();
        
        if($this->_start) {
            $tag->setAttribute('start', $this->_start);
        }

        if($this->_isReversed) {
            $tag->setAttribute('reversed', 'reversed');
        }

        $data = $this->_data;

        if(!$this->_isDataIterable() && $data !== null) {
            $data = [$data];
        }
        
        if(empty($data)) {
            return '';
        }
        
        $renderContext = new aura\html\widget\util\RendererContext($this);

        foreach($data as $key => $value) {
            $liTag = new aura\html\Tag('li');
            $articleTag = new aura\html\Tag('article');

            $renderContext->iterate($key, $articleTag, $liTag);
            $value = $renderContext->renderCell($value, $this->_renderer);

            if($value === null || $renderContext->shouldSkipRow()) {
                continue;
            }
            
            $children->push($liTag->renderWith(
                $articleTag->renderWith($value)
            ));
        }
        
        if($children->isEmpty()) {
            return '';
        }
        
        return $tag->renderWith($children, true);
    }
}