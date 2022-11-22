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

use df\core;
use df\opal;

class CollectionList extends Base implements IDataDrivenListWidget, IMappedListWidget, Dumpable
{
    use TWidget_DataDrivenList;
    use TWidget_MappedList;
    use TWidget_RendererContextProvider;

    public const PRIMARY_TAG = 'div.list.collection';

    public $paginator;

    protected $_errorMessage = 'No results to display';
    protected $_renderIfEmpty = true;
    protected $_showHeader = true;
    protected $_mode = 'get';
    protected $_postEvent = 'paginate';

    public function __construct(arch\IContext $context, $data, core\collection\IPaginator $paginator = null)
    {
        parent::__construct($context);

        $this->setData($data);

        if ($paginator === null && $data instanceof core\collection\IPageable) {
            $paginator = $data->getPaginator();
        }

        if ($paginator) {
            $this->paginator = self::factory($context, 'Paginator', [$paginator]);
        }
    }

    public function shouldRenderIfEmpty(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_renderIfEmpty = $flag;
            return $this;
        }

        return $this->_renderIfEmpty;
    }

    public function shouldShowHeader(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_showHeader = $flag;
            return $this;
        }

        return $this->_showHeader;
    }

    public function setMode(string $mode)
    {
        switch ($mode) {
            case 'post':
            case 'get':
                $this->_mode = $mode;

                if ($this->paginator) {
                    $this->paginator->setMode($this->_mode);
                }
                break;

            default:
                throw Exceptional::InvalidArgument(
                    'Invalid paginator mode: ' . $mode
                );
        }

        return $this;
    }

    public function getMode(): string
    {
        return $this->_mode;
    }

    public function setPostEvent(string $event)
    {
        $this->_postEvent = $event;

        if ($this->paginator) {
            $this->paginator->setPostEvent($event);
        }

        return $this;
    }

    public function getPostEvent()
    {
        return $this->_postEvent;
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
        $rows = new aura\html\ElementContent();

        $renderContext = $this->getRendererContext();
        $renderContext->reset();
        $empty = false;

        if (!$this->_isDataIterable()) {
            $empty = true;
        }

        $headRow = new aura\html\Element('tr');
        $orderData = $query = $request = null;
        $keyMap = [];

        if ($this->paginator) {
            $this->paginator->setMode($this->_mode);
            $this->paginator->setPostEvent($this->_postEvent);
            $pageData = $this->paginator->getPageData();

            if ($pageData instanceof opal\query\IPaginator) {
                $orderData = $pageData->getOrderDirectives();
                $orderFields = $pageData->getOrderableFieldDirectives();

                if (empty($orderData) || empty($orderFields)) {
                    $orderData = null;
                } else {
                    $keyMap = $pageData->getKeyMap();

                    if ($this->_mode == 'get') {
                        $request = clone $this->_context->request;
                        $query = $request->getQuery();
                    } else {
                        $request = null;
                        $query = new core\collection\Tree([
                            $keyMap['limit'] => $pageData->getLimit(),
                            $keyMap['page'] => $pageData->getPage(),
                            $keyMap['order'] => ''
                        ]);
                    }
                }
            }
        }

        $colClasses = [];

        foreach ($this->_fields as $fieldKey => $field) {
            $tagContent = [];
            $colClasses[$fieldKey] = [];

            foreach ($field->getHeaderList() as $key => $label) {
                $colClasses[$fieldKey][] = 'field-' . $key;

                if ($this->_showHeader && $orderData !== null && isset($orderFields[$key])) {
                    $nullOrder = 'ascending';
                    $isNullable = null;

                    if (isset($orderData[$key])) {
                        $direction = $orderData[$key]->getReversedDirection();
                        $isNullable = $orderData[$key]->isFieldNullable();
                        $nullOrder = $orderData[$key]->getNullOrder();
                        $isActive = true;
                    } else {
                        if (isset($orderFields[$key])) {
                            $direction = $orderFields[$key]->getDirection();
                        } else {
                            $direction = 'ASC';
                        }

                        $isActive = false;
                    }

                    $query->__set($keyMap['order'], $key . ' ' . $direction);

                    $class = 'order ' . strtolower(trim($direction, '!^*')) . ' null-' . $nullOrder;

                    if ($isActive) {
                        $class .= ' active';
                    }

                    if (!empty($tagContent)) {
                        $tagContent[] = ' / ';
                    }

                    if ($this->_mode == 'get') {
                        $tagContent[] = (new aura\html\Element('a', $label, [
                                'href' => $this->_context->uri->__invoke($request),
                                'class' => $class,
                                'rel' => 'nofollow'
                            ]))
                            ->render();
                    } else {
                        $tagContent[] = (new aura\html\Element('button', $label, [
                                'type' => 'submit',
                                'class' => $class,
                                'name' => 'formEvent',
                                'value' => $this->_postEvent . '(' . $query->toArrayDelimitedString() . ')',
                                'formnovalidate' => true
                            ]))
                            ->render();
                    }

                    if ($isActive && $isNullable !== false) {
                        $direction = trim($direction, '!^*') == 'DESC' ? 'ASC' : 'DESC';

                        switch ($nullOrder) {
                            case 'ascending':
                            case 'descending':
                                $direction .= '!';
                                $newOrder = 'last';
                                break;

                            default:
                                $newOrder = 'ascending';
                                break;
                        }

                        $query->__set($keyMap['order'], $key . ' ' . $direction);
                        $nullLabel = $newOrder == 'ascending' ? '○' : '●'; // @ignore-non-ascii

                        if ($this->_mode == 'get') {
                            $tagContent[] = (new aura\html\Element('a', $nullLabel, [
                                    'href' => $this->_context->uri->__invoke($request),
                                    'class' => 'null-order null-' . $newOrder,
                                    'rel' => 'nofollow'
                                ]))
                                ->render();
                        } else {
                            $tagContent[] = (new aura\html\Element('button', $nullLabel, [
                                    'type' => 'submit',
                                    'class' => 'null-order null-' . $newOrder,
                                    'name' => 'formEvent',
                                    'value' => $this->_postEvent . '(' . $query->toArrayDelimitedString() . ')',
                                    'formnovalidate' => true
                                ]))
                                ->render();
                        }
                    }
                } else {
                    $tagContent[] = $label;
                }
            }


            $colClasses[$fieldKey] = implode(' ', $colClasses[$fieldKey]);

            if (!$this->_showHeader) {
                continue;
            }

            $thTag = new aura\html\Element('th', $tagContent, ['class' => $colClasses[$fieldKey]]);
            $headRow->push($thTag->render());
        }

        if ($this->_showHeader) {
            $content = $headRow->render();
            $content->prepend("<table>\n<thead>\n")->append("\n</thead>\n\n<tbody>\n");
        } else {
            $content = new aura\html\ElementString("<table>\n<tbody>");
        }

        if (!$empty) {
            $empty = $first = $even = true;

            foreach ($this->_data as $j => $row) {
                $empty = false;
                $row = $renderContext->prepareRow($row);
                $rowTag = new aura\html\Element('tr');
                $rowTag->addClass(($even = !$even) ? 'even' : 'odd');
                $renderContext->iterate($j);

                foreach ($this->_fields as $key => $field) {
                    $attr = null;

                    if (isset($colClasses[$key])) {
                        $attr = ['class' => $colClasses[$key]];
                    }

                    if ($renderContext->shouldSkipCells()) {
                        $renderContext->iterateField($key, null, $rowTag);
                    } else {
                        $cellTag = new aura\html\Tag('td', $attr);
                        $renderContext->iterateField($key, $cellTag, $rowTag);
                        $value = $renderContext->renderCell($row, $field->renderer);

                        $rowTag->push($cellTag->renderWith($value));
                    }
                }

                if ($renderContext->divider !== null) {
                    if (!$first) {
                        $content->append((new aura\html\Element('tr.spacer', [
                            new aura\html\Element(
                                'td',
                                null,
                                ['colspan' => count($this->_fields)]
                            )
                        ]))->render());
                    }

                    if (!empty($renderContext->divider)) {
                        $content->append((new aura\html\Element('tr.divider', [
                            new aura\html\Element(
                                'td',
                                $renderContext->divider,
                                ['colspan' => count($this->_fields)]
                            )
                        ]))->render());
                    }

                    $even = false;
                }

                $first = false;

                if ($renderContext->shouldSkipRow()) {
                    continue;
                }

                $content->append($rowTag->render());
            }
        }

        if ($empty) {
            $paginator = $this->paginator ? $this->paginator->getPageData() : null;
            $shouldRender = $this->_renderIfEmpty;
            $errorMessage = $this->_errorMessage;
            $errorClass = 'error';

            if ($paginator && $paginator->getOffset() > 0) {
                $request = clone $this->_context->request;
                $request->query->{$paginator->getKeyMap()['page']} = 1;

                $errorMessage = (new FlashMessage($this->_context, $this->_context->_('This list appears to have gone past the last page - go back to the start...'), 'warning'))
                    ->setLink($request);
            }

            if (!$shouldRender) {
                return '';
            }

            $errorTag = new aura\html\Element('td.errorMessage', $errorMessage, ['colspan' => count($this->_fields)]);
            $errorTag->addClass('error');
            $content->append('<tr>' . $errorTag->render() . '</tr>');
        }

        $content->append("\n</tbody>\n</table>");
        $content = $tag->renderWith($content, true);

        if ($this->paginator) {
            $paginatorString = $this->paginator->toString();
            $content->prepend($paginatorString)->append($paginatorString);
        }

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
