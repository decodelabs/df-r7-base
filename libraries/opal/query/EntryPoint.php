<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;
    
class EntryPoint implements IEntryPoint, core\IApplicationAware {

    use TQuery_EntryPoint;
    use core\TApplicationAware;

    public function __construct(core\IApplication $application=null) {
        $this->_application = $application;
    }

    public function getApplication() {
        if(!$this->_application) {
            $this->_application = df\Launchpad::getActiveApplication();
        }

        return $this->_application;
    }
}