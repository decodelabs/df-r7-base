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

class AttributeList extends Base implements IDataDrivenListWidget, IMappedListWidget, core\IDumpable {
    
    const PRIMARY_TAG = 'div';
    
    use TWidget_DataDrivenList;
    use TWidget_MappedList;
    
    public function __construct(arch\IContext $context, $data) {
        $this->setData($data);
    }
    
    protected function _render() {
        $tag = $this->getTag();
        $tableTag = new aura\html\Tag('table');
        $rows = new aura\html\ElementContent();
        
        $renderContext = new aura\html\widget\util\RendererContext($this);
        
        $fields = $this->_fields;
        
        if(empty($fields)) {
            if(!$this->_isDataIterable()) {
                return '';
            }
            
            $fields = $this->_generateDefaultFields();
        }

        $renderContext->iterate(null);
        $data = $renderContext->prepareRow($this->_data);
        
        foreach($fields as $key => $field) {
            $row = new aura\html\ElementContent();
            $trTag = new aura\html\Tag('tr', ['class' => 'field-'.$key]);
            $thTag = new aura\html\Element('th', $field->getName());
            $tdTag = new aura\html\Tag('td');
            
            $renderContext->iterate($key, $tdTag, $trTag);
            $renderContext->iterateField($key, $tdTag, $trTag);
            $value = $renderContext->renderCell($data, $field->renderer);

            if($renderContext->shouldSkipRow()) {
                continue;
            }
            
            $row->push($thTag->render(), $tdTag->renderWith($value));
            $rows->push($trTag->renderWith($row, true));
        }
        
        return $tag->renderWith($tableTag->renderWith($rows, true));
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
