<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold\Index;

use df\arch\IComponent as Component;
use df\arch\scaffold\Record\DataProvider as RecordDataProvider;
use df\arch\scaffold\Component\HeaderBar as ScaffoldHeaderBar;
use df\arch\node\INode as Node;
use df\aura\view\IView as View;
use df\aura\view\content\WidgetContentProvider;

use Throwable;

trait DecoratorTrait
{
    // Nodes
    public function buildNode($content): View
    {
        $this->view = $this->apex->newWidgetView();

        $this->view->content->push(
            $this->apex->component('IndexHeaderBar'),
            $content
        );

        return $this->view;
    }


    // Components
    public function buildIndexHeaderBarComponent(array $args=[]): Component
    {
        return (new ScaffoldHeaderBar($this, 'index', $args))
            ->setTitle($this->renderDirectoryTitle())
            ->setBackLinkRequest($this->getIndexParentUri());
    }
}
