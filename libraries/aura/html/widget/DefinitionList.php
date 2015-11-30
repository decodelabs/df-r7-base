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
    use TWidget_RendererProvider;
    use TWidget_RendererContextProvider;

    protected $_renderIfEmpty = true;

    public function __construct(arch\IContext $context, $data, $renderer=null) {
        parent::__construct($context);

        $this->setData($data);
        $this->setRenderer($renderer);
    }

    protected function _render() {
        $tag = $this->getTag();
        $children = new aura\html\ElementContent();

        $renderContext = $this->getRendererContext();
        $renderContext->reset();
        $empty = true;

        if($this->_renderer) {
            foreach($this->_data as $key => $row) {
                $row = $renderContext->prepareRow($row);
                $field = new aura\html\widget\util\Field($key, $key, $this->_renderer);

                $empty = false;
                $this->_renderRow($children, $renderContext, $field, $key, $row);
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

            foreach($fields as $key => $field) {
                $empty = false;
                $this->_renderRow($children, $renderContext, $field, $key, $data);
            }
        }

        if($empty && $this->_renderIfEmpty) {
            return null;
        }

        return $tag->renderWith($children, true);
    }

    protected function _renderRow($children, $renderContext, $field, $key, $data) {
        $dtTag = new aura\html\Element('dt');
        $ddTag = new aura\html\Tag('dd');

        $renderContext->iterateField($key, $ddTag, null, $dtTag);
        $value = $renderContext->renderCell($data, $field->renderer);

        if($renderContext->shouldSkipRow()) {
            return;
        }

        if($dtTag->isEmpty()) {
            $dtTag->push($field->getName());
        }

        $children->push($dtTag->render(), $ddTag->renderWith($value));
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
            'tag' => $this->getTag()
        ];
    }
}
