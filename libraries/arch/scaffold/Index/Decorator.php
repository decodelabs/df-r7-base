<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold\Index;

use df\arch\IComponent as Component;
use df\arch\Scaffold;
use df\arch\node\INode as Node;
use df\aura\view\IView as View;
use df\arch\navigation\menu\IEntryList as MenuEntryList;
use df\aura\html\widget\Menu as MenuWidget;

interface Decorator extends Scaffold
{
    // Nodes
    public function indexHtmlNode();
    public function buildNode($content): View;

    // Components
    public function buildIndexHeaderBarComponent(array $args=[]): Component;
}
