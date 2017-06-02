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
    use TWidget_RendererContextProvider;

    const PRIMARY_TAG = 'span';

    protected $_limit = null;

    public function __construct(arch\IContext $context, $data, $renderer=null) {
        parent::__construct($context);

        $this->setData($data);
        $this->setRenderer($renderer);
    }

    public function setLimit(?int $limit) {
        $this->_limit = $limit ? abs($limit) : $limit;
        return $this;
    }

    public function getLimit(): ?int {
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

        $renderContext = $this->getRendererContext();
        $renderContext->reset();
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

            $children->push(new aura\html\Element('em.inactive', $this->_context->_('...and %c% more', ['%c%' => $more])));
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
            'tag' => $this->getTag()
        ];
    }
}
