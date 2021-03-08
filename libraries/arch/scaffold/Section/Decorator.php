<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold\Section;

use df\arch\IComponent as Component;
use df\arch\Scaffold;
use df\arch\node\INode as Node;
use df\aura\view\IView as View;
use df\arch\navigation\menu\IEntryList as MenuEntryList;
use df\aura\html\widget\Menu as MenuWidget;

interface Decorator extends Scaffold
{
    // Components
    public function buildSectionHeaderBarComponent(array $args=[]): Component;
}
