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
    
class PanelSet extends Base {

    const PRIMARY_TAG = 'div';

    protected $_panels = [];

    public function __construct(arch\IContext $context) {
        $this->_context = $context;
    }


    protected function _render() {
        if(empty($this->_panels)) {
            return '';
        }

        $cells = [];

        foreach($this->_panels as $id => $panel) {
            $cellTag = new aura\html\Tag('article', ['class' => 'widget-panel field-'.$id, 'style' => 'width: '.$panel['width'].'%;']);
            $bodyTag = new aura\html\Tag('div', ['class' => 'body']);
            $cells[] = $cellTag->renderWith($bodyTag->renderWith($panel['content']));
        }

        return $this->getTag()->renderWith($cells);
    }



    public function addPanel($id, $width, $content) {
        $this->_panels[$id] = [
            'width' => $this->_normalizePercent($width),
            'content' => $content
        ];

        return $this;
    }

    public function setPanelWidth($id, $width) {
        if(isset($this->_panels[$id])) {
            $this->_panels[$id]['width'] = $this->_normalizePercent($width);
        }
    }

    public function getPanelWidth($id) {
        if(isset($this->_panels[$id])) {
            return $this->_panels[$id]['width'];
        }
    }

    public function setPanelContent($id, $content) {
        if(isset($this->_panels[$id])) {
            $this->_panels[$id]['content'] = $content;
        }

        return $this;
    }

    public function getPanelContent($id) {
        if(isset($this->_panels[$id])) {
            return $this->_panels[$id]['content'];
        }
    }

    public function removePanel($id) {
        unset($this->_panels[$id]);
        return $this;
    }

    protected function _normalizePercent($value) {
        if(substr($value, -1) == '%') {
            $value = substr($value, 0, -1);
        }

        $value = (float)$value;

        if($value > 100) {
            $value = 100;
        }

        if($value < 0) {
            $value = 0.1;
        }

        return $value;
    }
}