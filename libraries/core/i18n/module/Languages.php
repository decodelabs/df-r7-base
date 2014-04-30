<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\i18n\module;

use df\core;

class Languages extends Base implements ILanguagesModule, core\i18n\module\generator\IModule {
        
    const MODULE_NAME = 'languages';
        
    public function getName($id) {
        $this->_loadData();
        
        $parts = explode('_', $id);
        $id = strtolower(array_shift($parts));
        
        if(!empty($parts)) {
            $temp = $id;
            
            $id .= '_'.strtoupper(array_shift($parts));
            
            if(!isset($this->_data[$id])) {
                $id = $temp;
            }
        }
        
        if(isset($this->_data[$id])) {
            return $this->_data[$id];    
        }
        
        return $id;
    }
    
    public function getList(array $ids=null) {
        $this->_loadData();
        $output = [];
        
        foreach($this->_data as $key => $name) {
            if($ids !== null && !in_array($key, $ids)) {
                continue;
            }

            if(strlen($key) == 2) {
                $output[$key] = $name;    
            }    
        }
                
        return $output;
    }
    
    public function getExtendedList() {
        $this->_loadData();
        return $this->_data;    
    }
    
    public function isValidId($id) {
        $this->_loadData();
        return isset($this->_data[strtolower($id)]);
    }

// Generator
    public function _convertCldr(core\i18n\ILocale $locale, \SimpleXMLElement $doc) {
        $output = [];
        
        if(isset($doc->localeDisplayNames->languages->language)) {
            foreach($doc->localeDisplayNames->languages->language as $language) {
                $type = (string)$language['type'];
                
                if(!isset($output[$type])) {
                    $output[$type] = (string)$language;
                }    
            }   
            
            $collator = new \Collator($locale->toString());
            $collator->asort($output);
        }
        
        return $output; 
    }
}
