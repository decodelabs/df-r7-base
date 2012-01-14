<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\i18n\module;

use df\core;

class Scripts extends Base implements IScriptsModule, core\i18n\module\generator\IModule {
    
    const MODULE_NAME = 'scripts';
    
    public function getName($id) {
        $this->_loadData();
        $id = ucfirst(strtolower($id));
        
        if(isset($this->_data[$id])) {
            return $this->_data[$id];
        }
        
        return $id;
    }
    
    public function getList() {
        $this->_loadData();
        return $this->_data;
    }
    
    public function isValidId($id) {
        $this->_loadData();
        return isset($this->_data[$id]);
    }
    
    
// Generator
    public function _convertCldr(core\i18n\ILocale $locale, \SimpleXMLElement $doc) {
        $output = array();
        
        if(isset($doc->localeDisplayNames->scripts->script)) {
            foreach($doc->localeDisplayNames->scripts->script as $script) {
                $type = (string)$script['type'];
                
                if(isset($output[$type])) {
                    $output[$type] = (string)$script;
                }    
            }  
            
            $collator = new \Collator($locale->toString());
            $collator->asort($output);
        }
        
        return $output; 
    }
}
