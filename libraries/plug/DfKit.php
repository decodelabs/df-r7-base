<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug;

use df;
use df\core;
use df\plug;
use df\arch;
use df\aura;

class DfKit implements arch\IDirectoryHelper {
    
    use arch\TDirectoryHelper;
    use aura\view\TViewAwareDirectoryHelper;

    protected function _init() {
        if(!$this->view) {
            throw new aura\view\RuntimeException('View is not available in plugin context');
        }
    }

    public function init($requireJsUrl) {
        $this->view->linkJs($requireJsUrl, 1, [
            'data-main' => $this->view->uri('asset://lib/df-kit/require.js')
        ]);

        return $this;
    }

    public function load($module) {
        $current = $this->view->bodyTag->getDataAttribute('require');
        $modules = core\collection\Util::flattenArray(func_get_args());

        if(!empty($current)) {
            $modules = array_unique(array_merge(explode(' ', $current), $modules));
        }
        
        $this->view->bodyTag->setDataAttribute('require', implode(' ', $modules));
        return $this;

    }
}