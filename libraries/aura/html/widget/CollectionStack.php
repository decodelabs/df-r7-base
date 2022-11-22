<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use DecodeLabs\Exceptional;
use DecodeLabs\Glitch\Dumpable;

use df\arch;
use df\aura;

class CollectionStack extends Base implements IDataDrivenListWidget, IMappedListWidget, Dumpable
{
    use TWidget_DataDrivenList;
    use TWidget_MappedList;
    use TWidget_RendererContextProvider;

    public const PRIMARY_TAG = 'div.list.collection.stack';

    protected $_errorMessage = 'No results to display';
    protected $_renderIfEmpty = true;
    protected $_postEvent = 'paginate';
    protected $_colWidth = null;

    public function __construct(arch\IContext $context, $data)
    {
        parent::__construct($context);

        $this->setData($data);
    }

    public function shouldRenderIfEmpty(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_renderIfEmpty = $flag;
            return $this;
        }

        return $this->_renderIfEmpty;
    }

    public function setColWidth(?string $width)
    {
        $this->_colWidth = $width;
        return $this;
    }

    public function getColWidth(): ?string
    {
        return $this->_colWidth;
    }


    // Render
    protected function _render()
    {
        if (empty($this->_fields)) {
            throw Exceptional::Runtime(
                'Collection list widgets must have at least one field'
            );
        }

        $tag = $this->getTag();

        $renderContext = $this->getRendererContext();
        $renderContext->reset();
        $empty = false;

        if (!$this->_isDataIterable()) {
            $empty = true;
        }

        $headTags = [];
        $rowClasses = [];
        $rows = [];
        $even = true;
        $first = true;

        foreach ($this->_fields as $fieldKey => $field) {
            $tagContent = [];
            $rowClasses[$fieldKey] = [];

            foreach ($field->getHeaderList() as $key => $label) {
                $rowClasses[$fieldKey][] = 'field-' . $key;
                $tagContent[] = $label;
            }


            $rowClasses[$fieldKey] = implode(' ', $rowClasses[$fieldKey]);
            $thTag = new aura\html\Element('th', $tagContent, [
                'class' => $rowClasses[$fieldKey],
                'width' => $this->_colWidth
            ]);
            $rowTag = new aura\html\Element('tr');

            if (!$first) {
                $rowTag->addClass(($even = !$even) ? 'even' : 'odd');
            }

            $rowTag->push($thTag->render());
            $rows[$fieldKey] = $rowTag;
            $first = false;
        }

        if (!$empty) {
            $empty = $first = $even = true;

            foreach ($this->_data as $j => $row) {
                $empty = false;
                $row = $renderContext->prepareRow($row);
                $renderContext->iterate($j);

                $fieldNum = 0;
                $genTag = new aura\html\Tag('span');

                foreach ($this->_fields as $fieldKey => $field) {
                    $attr = [];

                    if (isset($rowClasses[$fieldKey])) {
                        $attr = ['class' => $rowClasses[$fieldKey]];
                    }

                    if ($fieldNum === 0) {
                        $attr['width'] = $this->_colWidth;
                    }

                    $cellTag = new aura\html\Tag($fieldNum === 0 ? 'th' : 'td', $attr);
                    $renderContext->iterateField($fieldKey, $cellTag, $genTag);
                    $value = $renderContext->renderCell($row, $field->renderer);

                    $cellTag->addClasses($genTag->getClasses());
                    $cellTag->setStyles($genTag->getStyles());

                    $rows[$fieldKey]->push($cellTag->renderWith($value));

                    $fieldNum++;

                    /*
                    if ($renderContext->divider !== null) {
                        if (!$first) {
                            $rows[$fieldKey]->append((new aura\html\Element(
                                'td.spacer', null,
                                ['rowspan' => count($this->_fields)]
                                ))->render());
                        }

                        if (!empty($renderContext->divider)) {
                            $content->append((new aura\html\Element(
                                'td.divider',
                                $renderContext->divider,
                                ['rowspan' => count($this->_fields)]
                            ))->render());
                        }

                        $even = false;
                    }
                     */
                }

                $first = false;
            }
        }


        if ($empty) {
            $shouldRender = $this->_renderIfEmpty;
            $errorMessage = $this->_errorMessage;
            $errorClass = 'error';

            if (!$shouldRender) {
                return '';
            }

            $errorTag = new aura\html\Element('td.errorMessage', $errorMessage, ['colspan' => count($this->_fields)]);
            $errorTag->addClass('error');
            $table = new aura\html\Element('table', new aura\html\Element('tr', $errorTag));
        } else {
            $table = new aura\html\Element('table', $rows);
        }

        $content = $tag->renderWith($table, true);

        return $content;
    }



    // Error message
    public function setErrorMessage(string $message = null)
    {
        $this->_errorMessage = $message;
        return $this;
    }

    public function getErrorMessage()
    {
        return $this->_errorMessage;
    }

    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'properties' => [
            '%data' => count($this->_data) . ' rows',
            '*errorMessage' => $this->_errorMessage,
            '%tag' => $this->getTag()
        ];

        yield '^values' => $this->_fields;
    }
}
