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
    use TWidget_RendererProvider;
    use TWidget_RendererContextProvider;

    public function __construct(arch\IContext $context, $data=null, $renderer=null) {
        $this->setData($data);
        $this->setRenderer($renderer);
    }
    
    protected function _render() {
        $tag = $this->getTag();
        $tableTag = new aura\html\Tag('table');
        $rows = new aura\html\ElementContent();
        
        $renderContext = $this->getRendererContext();
        $renderContext->reset();
        $even = true;

        if($this->_renderer) {
            foreach($this->_data as $key => $row) {
                $row = $renderContext->prepareRow($row);
                $field = new aura\html\widget\util\Field($key, $key, $this->_renderer);

                $this->_renderRow($rows, $renderContext, $field, $key, $row, $even);
            }
        } else {
            $data = $renderContext->prepareRow($this->_data);
            $fields = $this->_fields;
            
            if(empty($fields)) {
                if(!$this->_isDataIterable()) {
                    return '';
                }
                
                $fields = $this->_generateDefaultFields();
            }

            $renderContext->iterate(null);
            
            foreach($fields as $key => $field) {
                $this->_renderRow($rows, $renderContext, $field, $key, $data, $even);
            }
        }
        
        return $tag->renderWith($tableTag->renderWith($rows, true));
    }

    protected function _renderRow($rows, $renderContext, $field, $key, $data, &$even) {
        $row = new aura\html\ElementContent();
        $trTag = new aura\html\Tag('tr', ['class' => 'field-'.$key]);
        $trTag->addClass(($even = !$even) ? 'even' : 'odd');
        $thTag = new aura\html\Element('th');
        $tdTag = new aura\html\Tag('td');

        $renderContext->iterate($key, $tdTag, $trTag, $thTag);
        $renderContext->iterateField($key, $tdTag, $trTag, $thTag);
        $value = $renderContext->renderCell($data, $field->renderer);

        if($renderContext->divider !== null) {
            if(!$rows->isEmpty()) {
                $rows->push((new aura\html\Element('tr.spacer', [
                    new aura\html\Element(
                        'td', null,
                        ['colspan' => 2]
                    )
                ]))->render());
            }

            if($renderContext->divider !== true) {
                $rows->push((new aura\html\Element('tr.divider', [
                    new aura\html\Element(
                        'td', 
                        $renderContext->divider,
                        ['colspan' => 2]
                    )
                ]))->render());
            }

            $even = false;
        }

        if($renderContext->shouldSkipRow()) {
            return;
        }

        if($thTag->isEmpty()) {
            $thTag->push($field->getName());
        }
        
        $row->push($thTag->render(), $tdTag->renderWith($value));
        $rows->push($trTag->renderWith($row, true));
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
