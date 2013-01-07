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

class CollectionList extends Base implements IDataDrivenListWidget, IMappedListWidget, core\IDumpable {
    
    const PRIMARY_TAG = 'div';
    
    use TWidget_DataDrivenList;
    use TWidget_MappedList;
    
    public $paginator;
    
    protected $_errorMessage = 'No results to display';
    
    public function __construct(arch\IContext $context, $data, core\collection\IPaginator $paginator=null) {
        $this->setData($data);
        
        if($paginator === null && $data instanceof core\collection\IPageable) {
            $paginator = $data->getPaginator();
        }
        
        if($paginator) {
            $this->paginator = self::factory($context, 'Paginator', array($paginator));
        }
    }
    
    protected function _render() {
        if(empty($this->_fields)) {
            throw new RuntimeException(
                'Collection list widgets must have at least one field'
            );
        }
        
        $tag = $this->getTag();
        $rows = new aura\html\ElementContent();
        
        $renderContext = new aura\html\widget\util\RendererContext($this);
        $context = $this->getView()->getContext();
        $empty = false;
        
        if(!$this->_isDataIterable()) {
            $empty = true;
        }
        
        $headRow = new aura\html\Element('tr');
        $orderData = null;
        
        if($this->paginator) {
            $pageData = $this->paginator->getPageData();
            
            if($pageData instanceof core\collection\IOrderablePaginator) {
                $orderData = $pageData->getOrderDirectives();
                $orderFields = $pageData->getOrderableFieldNames();
                
                if(empty($orderData) || empty($orderFields)) {
                    $orderData = null;
                } else {
                    $keyMap = $pageData->getKeyMap();
                    $request = clone $context->getRequest();
                    $query = $request->getQuery();
                }
            }
        }
        
        foreach($this->_fields as $key => $field) {
            $tagContent = $field->getName();
            
            if($orderData !== null && in_array($key, $orderFields)) {
                if(isset($orderData[$key])) {
                    if($orderData[$key]->isAscending()) {
                        $direction = 'DESC';
                    } else {
                        $direction = 'ASC';
                    }
                    
                    $isActive = true;
                } else {
                    $direction = 'ASC';
                    $isActive = false;
                }
                
                $query->__set($keyMap['order'], $key.' '.$direction);
                
                $class = 'link-order-'.strtolower($direction);
                
                if($isActive) {
                    $class .= ' link-order-active';
                }
                
                $tagContent = new aura\html\Element('a', $tagContent, [
                    'href' => $context->normalizeOutputUrl($request),
                    'class' => $class,
                    'rel' => 'nofollow'
                ]);
            }
            
            $thTag = new aura\html\Element('th', $tagContent);
            $headRow->push($thTag->render());
        }
        
        $content = $headRow->render();
        $content->prepend("<table>\n<thead>\n")->append("\n</thead>\n\n<tbody>\n");
        
        if(!$empty) {
            $empty = true;
            
            foreach($this->_data as $j => $row) {
                $empty = false;
                $rowTag = new aura\html\Element('tr');
                $renderContext->iterate($j);
                
                foreach($this->_fields as $key => $field) {
                    $cellTag = new aura\html\Tag('td');
                    $renderContext->iterateField($key, $cellTag, $rowTag);
                    $value = $field->render($row, $renderContext);
                    
                    $rowTag->push($cellTag->renderWith($value));
                }
                
                $content->append($rowTag->render(true));
            }
        }
        
        if($empty) {
            $errorTag = new aura\html\Element('td', $this->_errorMessage, array('colspan' => count($this->_fields)));
            $errorTag->addClass('state-error');
            $content->append('<tr>'.$errorTag->render().'</tr>');
        }
        
        $content->append("\n</tbody>\n</table>");
        $content = $tag->renderWith($content, true);
        
        if($this->paginator) {
            $paginatorString = $this->paginator->setRenderTarget($this->getRenderTarget())->toString();
            $content->prepend($paginatorString)->append($paginatorString);
        }
        
        return $content;
    }
    

    
// Error message
    public function setErrorMessage($message) {
        $this->_errorMessage = $message;
        return $this;
    }
    
    public function getErrorMessage() {
        return $this->_errorMessage;
    }
    
    
// Dump
    public function getDumpProperties() {
        return [
            'data' => count($this->_data).' rows',
            'fields' => $this->_fields,
            'errorMessage' => $this->_errorMessage,
            'tag' => $this->getTag(),
            'renderTarget' => $this->_getRenderTargetDisplayName()
        ];
    }
}
