<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use df;
use df\core;
use df\aura;
use df\opal;
use df\arch;

class Paginator extends Base implements core\IDumpable {
    
    const PRIMARY_TAG = 'nav';
    
    protected $_prevText = null;
    protected $_nextText = null;
    protected $_renderDetails = true;
    protected $_pageData;
    
    public function __construct(arch\IContext $context, $data) {
        if($data instanceof core\collection\IPageable) {
            $data = $data->getPaginator();
        }
        
        if(!$data instanceof core\collection\IPaginator) {
            throw new InvalidArgumentException(
                'Paginator requires an instance of core\\collection\\IPaginator'
            );
        }
        
        $this->_pageData = $data;
    }
    
    protected function _render() {
        $renderTarget = $this->getRenderTarget();
        $context = $renderTarget->getView()->getContext();
        $enabled = true;
        
        if(!$limit = $this->_pageData->getLimit()) {
            return '';
        }
        
        $offset = $this->_pageData->getOffset();
        $currentPage = round($offset / $limit) + 1;
        $total = $this->_pageData->countTotal();
        $totalPages = ceil($total / $limit);
        
        if($totalPages <= 1) {
            return '';
        }
        
        if($currentPage > $totalPages) {
            $currentPage = $totalPages;
        }
        
        $request = clone $context->getRequest();
        $query = $request->getQuery();
        
        $map = $this->_pageData->getKeyMap();
        $linkList = array();
        
        
        // Prev
        if($currentPage != 1) {
            $query->__set($map['page'], $currentPage - 1);
            
            if($this->_prevText === null) {
                $prevText = '←';
            } else {
                $prevText = $this->_prevText;
            }
            
            $element = new aura\html\Element('a', $prevText, array(
                'href' => $context->normalizeOutputUrl($request),
                'class' => 'link-prev',
                'rel' => 'prev'
            ));
            
            $linkList[] = $element->render();
        }
        
        // Inner
        $skip = false;
        
        for($i = 1; $i <= $totalPages; $i++) {
            $query->__set($map['page'], $i);
            $isCurrent = $i == $currentPage;
            
            if($isCurrent 
            || $totalPages <= 10 
            || $i < 3 
            || $i > $totalPages - 2 
            || ($i > $currentPage - 3 && $i < $currentPage + 3)) {
                $element = new aura\html\Element('a', $i, array(
                    'href' => $context->normalizeOutputUrl($request)
                ));
                
                if($isCurrent) {
                    $element->addClass('link-selected');
                }
                
                if($i == 1) {
                    $element->setAttribute('rel', 'first');
                } else if($i == $totalPages) {
                    $element->setAttribute('rel', 'last');
                }
                
                $linkList[] = $element->render();
                $skip = false;
            } else if(!$skip) {
                $linkList[] = '<span>..</span>';
                $skip = true;
            }
        }


        // Next
        if($currentPage != $totalPages) {
            $query->__set($map['page'], $currentPage + 1);
            
            if($this->_nextText === null) {
                $nextText = '→';
            } else {
                $nextText = $this->_nextText;
            }
            
            $element = new aura\html\Element('a', $nextText, array(
                'href' => $context->normalizeOutputUrl($request),
                'class' => 'link-next',
                'rel' => 'next'
            ));
            
            $linkList[] = $element->render();
        }
        
        if(empty($linkList)) {
            return '';
        }
        
        $content = array();
        
        if($this->_renderDetails) {
            $mLimit = $offset + $limit;
            
            if($mLimit > $total) {
                $mLimit = $total;
            }
            
            $messageData = array(
                '%offset%' => $offset + 1,
                '%limit%' => $mLimit,
                '%total%' => $total
            );
            
            if($messageData['%offset%'] == $messageData['%limit%']) {
                $message = $context->_(
                    'Showing %offset% of %total%',
                    $messageData
                );
            } else {
                $message = $context->_(
                    'Showing %offset% to %limit% of %total%',
                    $messageData
                );
            }
            
            $content[] = new aura\html\Element('div', $message, array('class' => 'block-details'));
        }
        
        $content[] = new aura\html\Element('div', $linkList, array('class' => 'block-links'));
        
        return $this->getTag()->renderWith($content, true);
    }
    
    
    public function getPageData() {
        return $this->_pageData;
    }
    
// Text
    public function setPrevText($text) {
        $this->_prevText = $text;
        return $this;
    }
    
    public function getPrevText() {
        return $this->_prevText;
    }
    
    public function setNextText($text) {
        $this->_nextText = $text;
        return $this;
    }
    
    public function getNextText() {
        return $this->_nextText;
    }
    
    public function shouldRenderDetails($flag=null) {
        if($flag !== null) {
            $this->_renderDetails = (bool)$flag;
            return $this;
        }
        
        return $this->_renderDetails; 
    }
    
    
// Dump
    public function getDumpProperties() {
        return [
            'prevText' => $this->_prevText,
            'nextText' => $this->_nextText,
            'renderDetails' => $this->_renderDetails,
            'pageDate' => $this->_pageData,
            'tag' => $this->getTag(),
            'renderTarget' => $this->_getRenderTargetDisplayName()
        ];
    }
}
