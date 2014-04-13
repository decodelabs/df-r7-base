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

        foreach($fields as $key => $field) {
            $ddTag = new aura\html\Element('dt', $field->getName());
            $dtTag = new aura\html\Tag('dd');

            $renderContext->iterateField($key, $dtTag);
            $value = $renderContext->renderCell($data, $field->renderer);

            if($renderContext->shouldSkipRow()) {
                continue;
            }
            
            $children->push($ddTag->render(), $dtTag->renderWith($value));
        }
        
        return $tag->renderWith($children, true);
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
