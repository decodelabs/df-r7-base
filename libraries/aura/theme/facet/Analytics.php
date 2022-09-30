<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\aura\theme\facet;

use df\aura;
use df\spur;

use DecodeLabs\Genesis;

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

        if (
            Genesis::$kernel->getMode() == 'Http' &&
            (
                Genesis::$environment->isProduction() ||
                isset($view->context->request->query->forceAnalytics)
            )
        ) {
            $this->getHandler()->apply($view);
        }
    }
}
