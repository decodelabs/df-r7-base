<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold\template;

use df;
use df\core;
use df\arch;
use df\aura;
use df\opal;

class AreaMenu extends arch\scaffold\Base {
    
    use arch\scaffold\TScaffold_IndexHeaderBarProvider;

    public function indexHtmlAction() {
        $container = $this->aura->getWidgetContainer();
        $container->push($this->directory->getComponent('IndexHeaderBar'));
        
        $menuId = (string)$this->_context->location;
        $menuId = dirname($menuId).'/'.ucfirst(basename($menuId));
        $container->addBlockMenu($menuId);

        return $container;
    }
}