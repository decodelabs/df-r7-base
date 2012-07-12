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

class BreadcrumbList extends Base implements IListWidget, core\IDumpable {
    
    const PRIMARY_TAG = 'nav';
    
    protected $_links = array();
    protected $_context;
    
    public function __construct(arch\IContext $context, array $trail=null) {
        if($trail !== null) {
            $this->setLinks($trail);
        } else {
            $this->_links = array();
        }

        $this->_context = $context;
    }
    
    protected function _render() {
        if(!$count = count($this->_links)) {
            return '';
        }
        
        $renderTarget = $this->getRenderTarget();
        $view = $renderTarget->getView();
        
        $content = new aura\html\ElementContent();
        $content->push(
            $containerTag = new aura\html\Element('span', null, array(
                'itemscope' => null,
                'itemtype' => 'http://data-vocabulary.org/Breadcrumb'
            ))
        );
        
        
        foreach($this->_links as $i => $linkWidget) {
            $linkWidget->setBody(
                new aura\html\Element('span', $linkWidget->getBody(), array(
                    'itemprop' => 'title'
                ))
            );
            
            $linkWidget->setAttribute('itemprop', 'url')
                ->setRenderTarget($renderTarget);
            
            // TODO: add access locks
            
            $containerTag->push($linkWidget->render());
            
            if($i < $count - 1) {
                $oldContainerTag = $containerTag;
                $oldContainerTag->push(
                    ' > ',
                
                    $containerTag = new aura\html\Element('span', null, array(
                        'itemscope' => null,
                        'itemprop' => 'child',
                        'itemtype' => 'http://data-vocabulary.org/Breadcrumb'
                    ))
                );
            }
        }
        
        return $this->getTag()->renderWith($content, true);
    }
    
    
// Links
    public function generateFromRequest(arch\IRequest $request=null) {
        if($request === null) {
            $request = $this->getRenderTarget()->getContext()->getRequest();
        }
        
        $parts = $request->getLiteralPathArray();
        $path = '';
        
        if($request->isDefaultArea()) {
            array_shift($parts);
        }
        
        $isDefaultAction = false;
        
        if($request->isDefaultAction()) {
            array_pop($parts);
            $isDefaultAction = true;
        }
        
        $count = count($parts);
        
        foreach($parts as $i => $part) {
            if(!$isDefaultAction && $i == $count - 1) {
                $path .= $part;
            } else {
                $path .= $part.'/';
            }
            
            $title = $part;
            
            if($i == 0) {
                $title = ltrim($title, $request::AREA_MARKER);
            }
            
            $title = ucwords(
                preg_replace('/([A-Z])/u', ' $1', str_replace(
                    array('-', '_'), ' ', $title
                ))
            );
            
            if($i == $count - 1) {
                $linkRequest = $request;
            } else {
                $linkRequest = $request::factory($path);
            }
            
            $this->addLink(new Link($this->_context, $linkRequest, $title));
        }
        
        return $this;
    }
    
    public function setLinks(array $links) {
        $this->_links = array();
        
        foreach($links as $link) {
            if($link instanceof ILinkWidget) {
                $this->addLink($link);
            }
        }
        
        return $this;
    }
    
    public function addLink(ILinkWidget $link) {
        $this->_links[] = $link;
        return $this;
    }
    
    public function getLinks() {
        return $this->_links;
    }
    
    public function removeLink($index) {
        unset($this->_links[$index]);
        return $this;
    }
    
    public function clearLinks() {
        $this->_links = array();
        return $this;
    }
    
    
// Dump
    public function getDumpProperties() {
        return [
            'links' => $this->_links,
            'tag' => $this->getTag(),
            'renderTarget' => $this->_getRenderTargetDisplayName()
        ];
    }
}
