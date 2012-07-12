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
    
    const PRIMARY_TAG = 'table';
    
    use TWidget_DataDrivenList;
    use TWidget_MappedList;
    
    public function __construct(arch\IContext $context, $data) {
        $this->setData($data);
    }
    
    protected function _render() {
        $tag = $this->getTag();
        $rows = new aura\html\ElementContent();
        
        $renderContext = new aura\html\widget\util\RendererContext($this);
        $renderTarget = $this->getRenderTarget();
        
        $fields = $this->_fields;
        
        if(empty($fields)) {
            if(!$this->_isDataIterable()) {
                return '';
            }
            
            $fields = $this->_generateDefaultFields();
        }
        
        foreach($fields as $key => $field) {
            $row = new aura\html\ElementContent();
            $trTag = new aura\html\Tag('tr');
            $thTag = new aura\html\Element('th', $field->getName());
            $tdTag = new aura\html\Tag('td');
            
            $renderContext->iterate($key, $tdTag, $trTag);
            $value = $field->render($this->_data, $renderTarget, $renderContext);
            
            $row->push($thTag->render(), $tdTag->renderWith($value));
            $rows->push($trTag->renderWith($row, true));
        }
        
        return $tag->renderWith($rows, true);
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
