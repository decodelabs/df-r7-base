<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\feed\extension\syndication;

use df;
use df\core;
use df\spur;

class FeedReader implements spur\feed\IFeedReaderPlugin {
    
    use spur\feed\TFeedReader;
    
    protected static $_xPathNamespaces = array(
        'syn10' => 'http://purl.org/rss/1.0/modules/syndication/'
    );
        
    public function getUpdatePeriod() {
        $period = $this->_xPath->evaluate(
            'string('.$this->_xPathPrefix.'/syn10:updatePeriod)'
        );
        
        switch($period) {
            case 'hourly':
            case 'weekly':
            case 'yearly':
                break;
            
            default:
                $period = 'daily';
                break;
        }
        
        return $period;
    }
    
    
    public function getUpdateFrequency() {
        $frequency = $this->_xPath->evaluate(
            'number('.$this->_xPathPrefix.'/syn10:updateFrequency)'
        );
        
        if(!$frequency || $frequency < 1) {
            $frequency = 1;
        }
        
        return $frequency;
    }
    
    public function getUpdateFrequencyTicks() {
        $period = $this->getUpdatePeriod();
        $frequency = $this->getUpdateFrequency();
        $ticks = 1;
        
        switch($period) {
            case 'yearly':
                $ticks *= 52;
            case 'weekly':
                $ticks *= 7;
            case 'daily':
                $ticks *= 24;
            case 'hourly':
                $ticks *= 3600;
                break;
            default:
                break;
        }
        
        return $ticks;
    }
    
    public function getUpdateBase() {
        $date = null;
        $updateBase = $this->_xPath->evaluate(
            'string('.$this->_xPathPrefix.'/syn10:updateBase)'
        );
        
        if($updateBase) {
            $date = core\time\Date::factory($updateBase);
        }
        
        return $date;
    }
}