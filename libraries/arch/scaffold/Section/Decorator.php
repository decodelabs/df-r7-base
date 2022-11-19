<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold\Section;

use df\arch\IComponent as Component;
use df\arch\Scaffold;

interface Decorator extends Scaffold
{
    // Components
    public function buildSectionHeaderBarComponent(array $args = []): Component;
}
