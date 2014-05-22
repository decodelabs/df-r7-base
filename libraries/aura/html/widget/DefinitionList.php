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

class DefinitionList extends Base implements IDataDrivenListWidget, IMappedListWidget, core\IDumpable {
    
    const PRIMARY_TAG = 'dl';
    
    use TWidget_DataDrivenList;
    use TWidget_MappedList;

    protected $_renderIfEmpty = true;
    
    public function __construct(arch\IContext $context, $data) {
        $this->setData($data);
    }
    
    protected function _render() {
        $tag = $this->getTag();
        $children = new aura\html\ElementContent();
        
        $renderContext = new aura\html\widget\util\RendererContext($this);
        $fields = $this->_fields;
        
        if(empty($fields)) {
            if(!$this->_isDataIterable()) {
                return '';
            }
            
            $fields = $this->_generateDefaultFields();
        }
        
        $data = $renderContext->prepareRow($this->_data);
        $empty = true;

        foreach($fields as $key => $field) {
            $dtTag = new aura\html\Element('dt', $field->getName());
            $ddTag = new aura\html\Tag('dd');

            $renderContext->iterateField($key, $ddTag, $dtTag);
            $value = $renderContext->renderCell($data, $field->renderer);

            if($renderContext->shouldSkipRow()) {
                continue;
            }
            
            $empty = false;
            $children->push($dtTag->render(), $ddTag->renderWith($value));
        }

        if($empty && $this->_renderIfEmpty) {
            return null;
        }
        
        return $tag->renderWith($children, true);
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
            'data' => count($this->_data).' rows',
            'fields' => $this->_fields,
            'tag' => $this->getTag(),
            'renderTarget' => $this->_getRenderTargetDisplayName()
        ];
    }
}
