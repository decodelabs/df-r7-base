<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold;

use df;
use df\core;
use df\arch;
use df\aura;
use df\opal;

class AreaMenu extends Base {

    const HEADER_BAR = true;

    use TScaffold_IndexHeaderBarProvider;

    public function indexHtmlAction() {
        $view = $this->apex->newWidgetView();

        if(static::HEADER_BAR) {
            $view->content->push($this->apex->component('IndexHeaderBar'));
        }

        $menuId = (string)$this->context->location;
        $menuId = dirname($menuId).'/'.ucfirst(basename($menuId));
        $view->content->addBlockMenu($menuId);

        return $view;
    }
}