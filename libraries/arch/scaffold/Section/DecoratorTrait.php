<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold\Section;

use df\arch\IComponent as Component;
use df\arch\scaffold\Record\DataProvider as RecordDataProvider;
use df\arch\scaffold\Component\HeaderBar as ScaffoldHeaderBar;
use df\arch\node\INode as Node;
use df\aura\view\IView as View;
use df\aura\view\content\WidgetContentProvider;

use Throwable;

trait DecoratorTrait
{

    // Components
    public function buildSectionHeaderBarComponent(array $args=[]): Component
    {
        if ($this instanceof RecordDataProvider) {
            $icon = $this->getRecordIcon();
            $title = [
                ucfirst($this->getRecordItemName()).': ',
                $this->getRecordDescription()
            ];
            $backLink = $this->getRecordParentUri($this->getActiveRow());
        } else {
            $icon = $this->getDirectoryIcon();
            $title = $this->getDirectoryTitle();
            $backLink = $this->getNodeUri('index');
        }

        return (new ScaffoldHeaderBar($this, 'section', $args))
            ->setTitle($title)
            ->setIcon($icon)
            ->setBackLinkRequest($backLink);
    }

    public function renderSectionSelectorArea()
    {
        $node = $this->context->request->getNode();
        $method = 'render'.ucfirst($node).'SectionSelectorArea';

        if (method_exists($this, $method)) {
            return $this->{$method}();
        }
    }
}
