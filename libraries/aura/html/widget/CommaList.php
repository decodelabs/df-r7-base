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

    protected $_limit = null;

    public function __construct(arch\IContext $context, $data, $renderer=null) {
        $this->setData($data);
        $this->setRenderer($renderer);
    }

    public function setLimit($limit) {
        $limit = abs((int)$limit);

        if($limit == 0) {
            $limit = null;
        }

        $this->_limit = $limit;
        return $this;
    }

    public function getLimit() {
        return $this->_limit;
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
        $count = 0;
        $more = 0;

        foreach($data as $key => $value) {
            $cellTag = new aura\html\Tag('span');
            $renderContext->iterate($key, $cellTag);
            $value = $renderContext->renderCell($value, $this->_renderer);

            if($value === null || $renderContext->shouldSkipRow()) {
                continue;
            }

            if($this->_limit !== null && $count >= $this->_limit) {
                $more++;
                continue;
            }

            if(!$first) {
                $children->push(', ');
            }

            $first = false;
            $children->push($cellTag->renderWith($value));
            $count++;
        }

        if($more) {
            if(!$first) {
                $children->push(', ');
            }

            $context = $this->getView()->context;
            $children->push(new aura\html\Element('em', $context->_('...and %c% more', ['%c%' => $more])));
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
