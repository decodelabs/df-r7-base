<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\shared\widgets\_components;

use df;
use df\core;
use df\apex;
use df\arch;
use df\axis;
    
class SlugTreeBreadcrumbs extends arch\Component {

    protected $_node;

    protected function _init($node=null) {
        if($node) {
            $this->setNode($node);
        }
    }

    public function setNode(axis\unit\table\record\SlugTreeRecord $node) {
        $this->_node = $node;
        return $this;
    }

    public function getNode() {
        return $this->_node;
    }

    protected function _execute() {
        $trail = $this->_node->fetchParentPath();
        $trail[] = $this->_node;

        $output = new arch\navigation\breadcrumbs\EntryList();

        foreach($trail as $node) {
            $request = clone $this->request;
            $slug = $node['slug'];

            if(empty($slug)) {
                unset($request->query->slug);
            } else {
                $request->query->slug = $slug;
            }

            $output->addEntry(
                $output->newLink($request, $node['name'])  
                    ->setId($slug)
                    ->setDescription($node['description'])
                    ->setIcon('folder')
            );
        }
        
        $view = $this->getRenderTarget()->getView();

        return $view->html
            ->breadcrumbList($output)
                ->setSeparator('/');
    }
}