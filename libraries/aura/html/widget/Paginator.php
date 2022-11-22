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

class Paginator extends Base implements Dumpable
{
    public const PRIMARY_TAG = 'div.paginator';

    protected $_prevText = null;
    protected $_nextText = null;
    protected $_renderDetails = true;
    protected $_mode = 'get';
    protected $_postEvent = 'paginate';
    protected $_pageData;

    public function __construct(arch\IContext $context, $data)
    {
        parent::__construct($context);

        if ($data instanceof core\collection\IPageable) {
            $data = $data->getPaginator();
        }

        if (!$data instanceof core\collection\IPaginator) {
            $data = null;
        }

        $this->_pageData = $data;
    }

    public function setMode(string $mode)
    {
        switch ($mode) {
            case 'post':
            case 'get':
                $this->_mode = $mode;
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
        return $this;
    }

    public function getPostEvent()
    {
        return $this->_postEvent;
    }

    protected function _render()
    {
        if (!$this->_pageData) {
            return '';
        }

        $enabled = true;

        if (!$limit = $this->_pageData->getLimit()) {
            return '';
        }

        $offset = $this->_pageData->getOffset();
        $currentPage = $this->_pageData->getPage();
        $total = $this->_pageData->countTotal();
        $totalPages = ceil($total / $limit);

        if ($totalPages <= 1) {
            return '';
        }

        if ($currentPage > $totalPages) {
            $currentPage = $totalPages;
        }

        $map = $this->_pageData->getKeyMap();
        $linkList = [];

        if ($this->_mode == 'get') {
            $request = clone $this->_context->request;
            $query = $request->getQuery();
        } else {
            $request = null;

            $order = [];

            foreach ($this->_pageData->getOrderDirectives() as $dirName => $directive) {
                $order[] = $dirName . ' ' . $directive->getDirection();
            }

            $order = implode(',', $order);

            $query = new core\collection\Tree([
                $map['limit'] => $limit,
                $map['page'] => $currentPage,
                $map['order'] => $order
            ]);
        }



        // Prev
        if ($currentPage != 1) {
            $query->__set($map['page'], $currentPage - 1);

            if ($this->_prevText === null) {
                $prevText = '←'; // @ignore-non-ascii
            } else {
                $prevText = $this->_prevText;
            }

            if ($this->_mode == 'get') {
                $element = new aura\html\Element('a', $prevText, [
                    'href' => $this->_context->uri->__invoke($request),
                    'class' => 'prev',
                    'rel' => 'prev'
                ]);
            } else {
                $element = new aura\html\Element('button', $prevText, [
                    'type' => 'submit',
                    'class' => 'prev',
                    'name' => 'formEvent',
                    'value' => $this->_postEvent . '(' . $query->toArrayDelimitedString() . ')',
                    'formnovalidate' => true
                ]);
            }

            $linkList[] = $element->render();
        }

        // Inner
        $skip = false;

        for ($i = 1; $i <= $totalPages; $i++) {
            $query->__set($map['page'], $i);
            $isCurrent = $i == $currentPage;

            if ($isCurrent
            || $totalPages <= 10
            || $i < 3
            || $i > $totalPages - 2
            || ($i > $currentPage - 3 && $i < $currentPage + 3)) {
                if ($this->_mode == 'get') {
                    $element = new aura\html\Element('a', $i, [
                        'href' => $this->_context->uri->__invoke($request),
                        'class' => 'page'
                    ]);
                } else {
                    $element = new aura\html\Element('button', $i, [
                        'type' => 'submit',
                        'class' => 'page',
                        'name' => 'formEvent',
                        'value' => $this->_postEvent . '(' . $query->toArrayDelimitedString() . ')',
                        'formnovalidate' => true
                    ]);
                }

                if ($isCurrent) {
                    $element->addClass('active');
                }

                if ($i == 1) {
                    $element->setAttribute('rel', 'first');
                } elseif ($i == $totalPages) {
                    $element->setAttribute('rel', 'last');
                }

                $linkList[] = $element->render();
                $skip = false;
            } elseif (!$skip) {
                $linkList[] = new aura\html\Element('span', '..');
                $skip = true;
            }
        }


        // Next
        if ($currentPage != $totalPages) {
            $query->__set($map['page'], $currentPage + 1);

            if ($this->_nextText === null) {
                $nextText = '→'; // @ignore-non-ascii
            } else {
                $nextText = $this->_nextText;
            }

            if ($this->_mode == 'get') {
                $element = new aura\html\Element('a', $nextText, [
                    'href' => $this->_context->uri->__invoke($request),
                    'class' => 'next',
                    'rel' => 'next'
                ]);
            } else {
                $element = new aura\html\Element('button', $nextText, [
                    'type' => 'submit',
                    'class' => 'next',
                    'name' => 'formEvent',
                    'value' => $this->_postEvent . '(' . $query->toArrayDelimitedString() . ')',
                    'formnovalidate' => true
                ]);
            }

            $linkList[] = $element->render();
        }

        if (empty($linkList)) {
            return '';
        }

        $content = [];

        if ($this->_renderDetails) {
            $mLimit = $offset + $limit;

            if ($mLimit > $total) {
                $mLimit = $total;
            }

            if ($offset >= $mLimit) {
                $offset = $mLimit - 1;
            }

            $messageData = [
                '%offset%' => $offset + 1,
                '%limit%' => $mLimit,
                '%total%' => $total
            ];

            if ($messageData['%offset%'] == $messageData['%limit%']) {
                $message = $this->_context->_(
                    'Showing %offset% of %total%',
                    $messageData
                );
            } else {
                $message = $this->_context->_(
                    'Showing %offset% to %limit% of %total%',
                    $messageData
                );
            }

            $content[] = new aura\html\Element('div.counts', $message);
        }

        $content[] = new aura\html\Element('nav.pages', $linkList);

        return $this->getTag()
            ->renderWith($content, true);
    }


    public function getPageData()
    {
        return $this->_pageData;
    }

    // Text
    public function setPrevText($text)
    {
        $this->_prevText = $text;
        return $this;
    }

    public function getPrevText()
    {
        return $this->_prevText;
    }

    public function setNextText($text)
    {
        $this->_nextText = $text;
        return $this;
    }

    public function getNextText()
    {
        return $this->_nextText;
    }

    public function shouldRenderDetails(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_renderDetails = $flag;
            return $this;
        }

        return $this->_renderDetails;
    }

    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'properties' => [
            '*prevText' => $this->_prevText,
            '*nextText' => $this->_nextText,
            '*renderDetails' => $this->_renderDetails,
            '*pageData' => $this->_pageData,
            '%tag' => $this->getTag()
        ];
    }
}
