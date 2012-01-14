<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\i18n\module;

use df\core;

class Territories extends Base implements ITerritoriesModule, core\i18n\module\generator\IModule {
    
    const MODULE_NAME = 'territories';
    
    public function getName($id) {
        $this->_loadData();
        $id = strtoupper($id);
        
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
        $output = null;
        
        
        if(isset($doc->localeDisplayNames->territories->territory)) {
            $output = array();
            
            foreach($doc->localeDisplayNames->territories->territory as $territory) {
                $type = (string)$territory['type'];
                
                if(!is_numeric($type)) {
                    continue;
                }
                
                $output[(string)$type] = (string)$territory;
            }  
            
            
            $collator = new \Collator($locale->toString());
            $collator->asort($output);
        } 
        
        return $output; 
    }
}
