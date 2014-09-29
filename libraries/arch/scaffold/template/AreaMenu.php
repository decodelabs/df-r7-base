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
    
    const HEADER_BAR = true;

    use arch\scaffold\TScaffold_IndexHeaderBarProvider;

    public function indexHtmlAction() {
        $container = $this->aura->getWidgetContainer();

        if(static::HEADER_BAR) {
            $container->push($this->directory->getComponent('IndexHeaderBar'));
        }
        
        $menuId = (string)$this->_context->location;
        $menuId = dirname($menuId).'/'.ucfirst(basename($menuId));
        $container->addBlockMenu($menuId);

        return $container;
    }
}