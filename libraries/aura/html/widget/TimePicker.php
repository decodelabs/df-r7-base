<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use df;
use df\core;
use df\aura;
use df\arch;

class TimePicker extends DatePicker {
    
    const INPUT_TYPE = 'time';

    protected $_outputFormat = 'h:i';
    protected $_placeholder = 'hh:mm';

    protected function _getInputType() {
        if($this->_outputFormat != 'hh:mm') {
            return 'text';
        } else {
            return 'time';
        }
    }
    
    protected function _normalizeDateString($date) {
        if($date instanceof core\time\IDate) {
            $date = $this->_dateToString($date);
        }
        
        $date = (string)$date;
        
        if(!preg_match('/^[0-1]?[0-9]\:[0-5][0-9]$/', $date)) {
            try {
                $date = $this->_stringToDate($date);
                
                if($date !== null) {
                    $date = $this->_dateToString($date);
                }
            } catch(\Exception $e) {
                $date = null;
            }
        }
        
        return $date;
    }

    protected function _stringToDate($date) {
        return core\time\Date::factory((string)$date, true);
    }
    
    protected function _dateToString(core\time\IDate $date) {
        return $date->format($this->_outputFormat);
    }
}
