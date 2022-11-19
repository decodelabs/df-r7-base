<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold\Index;

use df\arch\IComponent as Component;
use df\arch\Scaffold;
use df\aura\view\IView as View;

interface Decorator extends Scaffold
{
    // Nodes
    public function indexHtmlNode();
    public function buildNode($content): View;

    // Components
    public function buildIndexHeaderBarComponent(array $args = []): Component;
}
