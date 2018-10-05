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

class Analytics extends Base
{
    protected $_handler;

    public function getHandler()
    {
        if (!$this->_handler) {
            $this->_handler = spur\analytics\Handler::factory();
        }

        return $this->_handler;
    }

    public function afterHtmlViewRender(aura\view\IHtmlView $view)
    {
        if (!$this->_checkEnvironment()) {
            return;
        }

        if ($view->context->getRunMode() == 'Http'
        && ($view->context->app->isProduction() || isset($view->context->request->query->forceAnalytics))) {
            $this->getHandler()->apply($view);
        }
    }
}
