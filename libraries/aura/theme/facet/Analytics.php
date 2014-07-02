<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\theme\facet;

use df;
use df\core;
use df\aura;
use df\arch;
use df\spur;

class Analytics extends Base {
    
    public function renderToHtml(aura\view\IHtmlView $view) {
        if($view->context->getRunMode() == 'Http') {
            spur\analytics\Handler::factory()->apply($view);
        }
    }
}