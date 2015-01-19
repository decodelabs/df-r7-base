<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\theme;

use df;
use df\core;
use df\aura;
use df\arch;

class Config extends core\Config {
    
    const ID = 'Theme';
    const CACHE_IN_MEMORY = false;
    
    public function getDefaultValues() {
        return [
            'default' => 'whitewash'
        ];
    }
    
    public function getThemeIdFor($area) {
        $area = ltrim($area, arch\Request::AREA_MARKER);

        if($this->values->has($area)) {
            return $this->values[$area];
        } else if($this->values->has('default')) {
            return $this->values['default'];
        } else {
            return 'shared';
        }
    }
}