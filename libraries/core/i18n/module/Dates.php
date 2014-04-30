<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\i18n\module;

use df\core;

class Dates extends Base implements core\i18n\module\generator\IModule {
    
    public function getCalendarList() {
        $this->_loadData();
        
        $output = $this->_data;
        unset($output['@default']);
        
        return array_keys($output);    
    }
    
    public function getDefaultCalendar() {
        $this->_loadData();
        
        if(isset($this->_data['@default']) 
        && isset($this->_data[$this->_data['@default']])) {
            return $this->_data['@default'];    
        }  
          
        return 'gregorian';
    }
    
    public function getDayName($day=null, $calendar=null) {
        $this->_loadData();
        
        $list = $this->getDayList($calendar);
        
        if($day === null) {
            $day = core\time\Date::factory('now')->format('w');  
        }
        
        switch(strtolower($day)) {
            case 0:
            case 7:
            case 'sun':
            case 'sunday':
                return $list['sun'];
                
            case 1:
            case 'mon':
            case 'monday':
                return $list['mon'];
                
            case 2:
            case 'tue':
            case 'tuesday':
                return $list['tue'];
                
            case 3:
            case 'wed':
            case 'wednesday':
                return $list['wed'];
                
            case 4:
            case 'thu':
            case 'thursday':
                return $list['thur'];
                
            case 5:
            case 'fri':
            case 'friday':
                return $list['fri'];
                
            case 6:
            case 'sat':
            case 'saturday':
                return $list['sat'];
        }            
    }
    
    public function getDayList($calendar=null) {
        $this->_loadData();
        
        $calendar = strtolower($calendar);
        
        if(!isset($this->_data[$calendar])) {
            $calendar = $this->getDefaultCalendar();
        }  
        
        $c = $this->_data[$calendar];
        
        if(is_string($c['days'])) {
            return $this->getDayList($c['days']);    
        }
        
        return $c['days']['full'];
    }
    
    public function getAbbreviatedDayList($calendar=null) {
        $this->_loadData();
        
        $calendar = strtolower($calendar);
        
        if(!isset($this->_data[$calendar])) {
            $calendar = $this->getDefaultCalendar();
        }   
        
        $c = $this->_data[$calendar];
        
        if(is_string($c['days'])) {
            return $this->getDayList($c['days']);    
        }
        
        if(is_string($c['days']['abbreviated'])) {
            return $this->getDayList($calendar);
        }
        
        return $c['days']['abbreviated'];
    }
    
    public function getMonthName($month=null, $calendar=null) {
        $this->_loadData();
        
        $list = $this->getMonthList($calendar);

        if($month === null) {
            $month = core\time\Date::now()->format('n');  
        }
        
        switch(strtolower($month)) {
            case 1:
            case 'jan':
            case 'january':
                return $list[1];
                
            case 2:
            case 'feb':
            case 'february':
                return $list[2];
                
            case 3:
            case 'mar':
            case 'march':
                return $list[3];
                
            case 4:
            case 'apr':
            case 'april':
                return $list[4];
                
            case 5:
            case 'may':
                return $list[5];
                
            case 6:
            case 'jun':
            case 'june':
                return $list[6];
                
            case 7:
            case 'jul':
            case 'july':
                return $list[7];
                
            case 8:
            case 'aug':
            case 'august':
                return $list[8];
                
            case 9:
            case 'sep':
            case 'september':
                return $list[9];
                
            case 10:
            case 'oct':
            case 'october':
                return $list[10];
                
            case 11:
            case 'nov':
            case 'november':
                return $list[11];
                
            case 12:
            case 'dec':
            case 'december':
                return $list[12];
        }            
    }
    
    public function getMonthList($calendar=null) {
        $this->_loadData();
        
        $calendar = strtolower($calendar);
        
        if(!isset($this->_data[$calendar])) {
            $calendar = $this->getDefaultCalendar();
        }
        
        $c = $this->_data[$calendar];
        
        if(is_string($c['months'])) {
            return $this->getMonthList($c['months']);    
        }
        
        return $c['months']['full'];
    }
    
    public function getAbbreviatedMonthList($calendar=null) {
        $this->_loadData();
        
        $calendar = strtolower($calendar);
        
        if(!isset($this->_data[$calendar])) {
            $calendar = $this->getDefaultCalendar();
        } 
        
        $c = $this->_data[$calendar];
        
        if(is_string($c['months'])) {
            return $this->getAbbreviatedMonthList($c['months']);    
        }
        
        if(is_string($c['months']['abbreviated'])) {
            return $this->getMonthList($calendar);
        }
        
        return $c['months']['abbreviated'];
    }

// Generator
    public function _convertCldr(core\i18n\ILocale $locale, \SimpleXMLElement $doc) {
        if(!isset($doc->dates->calendars)) {
            return null;    
        }
        
        $output = [];
        $calendars = $doc->dates->calendars;
        
        if(isset($calendars->{'default'})) {
            $output['@default'] = (string)$calendars->{'default'}['choice'];    
        } else {
            $output['@default'] = 'gregorian';   
        }
        
        
        foreach($calendars->calendar as $calendar) {
            $arr = [];    
            
            $this->_calendarDays($calendar, $arr);
            $this->_calendarMonths($calendar, $arr);
            
            if(isset($calendar->am)) {
                $arr['meridian']['am'] = (string)$calendar->am;    
            }
            if(isset($calendar->pm)) {
                $arr['meridian']['pm'] = (string)$calendar->pm;    
            }
            
            $this->_dateFormat($calendar, $arr);
            $this->_timeFormat($calendar, $arr);
            
            $output[(string)$calendar['type']] = $arr;
        }
        
        ksort($output);
        return $output; 
    }

    protected function _calendarDays($calendar, &$arr) {
        if(!isset($calendar->days)) {
            return;
        }
        
        if(isset($calendar->days->alias)) {
            $path = (string)$calendar->days->alias['path'];
            if(substr($path, 0, 14) == '../../calendar') {
                $arr['days'] = substr($path, 22, -7);    
            }    
            
            return;
        }
        
        $context = null;
        foreach($calendar->days->dayContext as $c) {
            if((string)$c['type'] == 'format') {
                $context = $c;
                break;
            }
        }
        
        if(!$context) {
            return;    
        }

        $arr['days'] = []; 
        
        if(isset($context->{'default'})) {
            $default = (string)$context->{'default'}['choice'];    
            
            if($default != 'wide' && $default != 'abbreviated') {
                continue;    
            }
            
            if($default == 'wide') {
                $default = 'full';    
            }
            
            $arr['days']['@default'] = $default;
        } else {
            $arr['days']['@default'] = 'wide';    
        }
        
        foreach($context->dayWidth as $set) {
            $type = (string)$set['type'];
            if($type != 'wide' && $type != 'abbreviated') {
                continue;    
            }
            
            if($type == 'wide') {
                $type = 'full';    
            }
            
            if(isset($set->alias)) {
                $path = (string)$set->alias['path'];
                if(substr($path, 0, 11) == '../dayWidth') {
                    $arr['days'][$type] = substr($path, 19, -2);
                    continue;   
                } else if(substr($path, 0, 16) == '../../dayContext') {
                    $set = $set->xpath($path);
                    $set = $set[0];
                } 
            }
            
            $arr['days'][$type] = [];
            
            foreach($set->day as $day) {
                $arr['days'][$type][(string)$day['type']] = (string)$day;    
            }    
        }
    }
    
    protected function _calendarMonths($calendar, &$arr) {
        if(!isset($calendar->months)) {
            return;
        }
        
        if(isset($calendar->months->alias)) {
            $path = (string)$calendar->months->alias['path'];
            if(substr($path, 0, 14) == '../../calendar') {
                $arr['months'] = substr($path, 22, -9);    
            }    
            
            return;
        }
        
        $context = null;
        foreach($calendar->months->monthContext as $c) {
            if((string)$c['type'] == 'format') {
                $context = $c;
                break;
            }
        }
        
        if(!$context) {
            return;    
        }

        $arr['months'] = []; 
        
        if(isset($context->{'default'})) {
            $default = (string)$context->{'default'}['choice'];    
            
            if($default != 'wide' && $default != 'abbreviated') {
                continue;    
            }
            
            if($default == 'wide') {
                $default = 'full';    
            }
            
            $arr['months']['@default'] = $default;
        } else {
            $arr['months']['@default'] = 'full';    
        }
        
        foreach($context->monthWidth as $set) {
            $type = (string)$set['type'];
            if($type != 'wide' && $type != 'abbreviated') {
                continue;    
            }
            
            if($type == 'wide') {
                $type = 'full';    
            }
            
            if(isset($set->alias)) {
                $path = (string)$set->alias['path'];
                if(substr($path, 0, 13) == '../monthWidth') {
                    $arr['months'][$type] = substr($path, 21, -2);
                    continue;   
                } else if(substr($path, 0, 18) == '../../monthContext') {
                    $set = $set->xpath($path);
                    $set = $set[0];
                } 
            }
            
            $arr['months'][$type] = [];
            
            foreach($set->month as $month) {
                $arr['months'][$type][(string)$month['type']] = (string)$month;    
            }    
        }
    }

    protected function _dateFormat($calendar, &$arr) {
        if(!isset($calendar->dateFormats)) {
            return;    
        }
        
        if(isset($calendar->dateFormats->alias)) {
            $path = (string)$calendar->dateFormats->alias['path'];
            if(substr($path, 0, 14) == '../../calendar') {
                $arr['dateFormat'] = substr($path, 22, -14);    
            }    
            
            return;
        }
        
        $arr['dateFormat'] = [];
        
        foreach($calendar->dateFormats->dateFormatLength as $set) {
            $type = (string)$set['type'];
            
            $arr['dateFormat'][$type] = (string)$set->dateFormat->pattern[0];
        }
    }
    
    protected function _timeFormat($calendar, &$arr) {
        if(!isset($calendar->timeFormats)) {
            return;    
        }
        
        if(isset($calendar->timeFormats->alias)) {
            $path = (string)$calendar->timeFormats->alias['path'];
            if(substr($path, 0, 14) == '../../calendar') {
                $arr['timeFormat'] = substr($path, 22, -14);    
            }    
            
            return;
        }
        
        $arr['timeFormat'] = [];
        
        foreach($calendar->timeFormats->timeFormatLength as $set) {
            $type = (string)$set['type'];
            
            $arr['timeFormat'][$type] = (string)$set->timeFormat->pattern[0];
        }
    }        
}
