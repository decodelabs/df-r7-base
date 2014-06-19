<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis;

use df;
use df\core;
use df\axis;
    
final class Context implements IContext {

    use axis\TUnit;
    use core\TContext;

    public function __construct(IModel $model) {
        $this->_model = $model;
        $this->application = df\Launchpad::$application;
    }

    public function getUnitType() {
        return 'context';
    }

    public function getUnitName() {
        return 'context';
    }

    public function _($phrase, array $data=null, $plural=null, $locale=null) {
        if($locale === null) {
            $locale = $this->_locale;
        }

        $translator = core\i18n\translate\Handler::factory('axis/Context', $locale);
        return $translator->_($phrase, $data, $plural);
    }   


// Helpers
    protected function _loadHelper($name) {
        $class = 'df\\plug\\model\\'.$this->application->getRunMode().$name;
        
        if(!class_exists($class)) {
            $class = 'df\\plug\\model\\'.$name;
            
            if(!class_exists($class)) {
                return $this->_loadSharedHelper($name);
            }
        }
        
        return new $class($this);
    }

    public function __get($key) {
        switch($key) {
            case 'model':
                return $this->_model;
                
            default:
                return $this->_getDefaultMember($key);
        }
    }
}