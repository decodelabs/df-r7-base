<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\ldap\adapter;

use df;
use df\core;
use df\opal;

class ActiveDirectory extends opal\ldap\Adapter {
    
    const BIND_REQUIRES_DN = false;
    const UID_ATTRIBUTE = 'sAMAccountName';
    
    protected function _flattenDn(opal\ldap\IDn $dn) {
        return $dn->implode(',', core\string\ICase::UPPER);
    }
    
    protected function _inflateDate($name, $date) {
        if($date == 0 || $date == null) {
            return null;
        }
        
        if(false !== strpos($date, '.')) {
            $parts = explode('.', $date);
            return core\time\Date::factory(array_shift($parts));
        } else {
            return core\time\Date::factory(
                bcsub(bcdiv($date, '10000000', 0), '11644473600')
            );
        }
    }
    
    protected function _deflateDate($name, $date) {
        $date = core\time\Date::factory($date);
        $ts = $date->toTimestamp();
        
        return bcmul(bcadd($ts, '11644473600'), '10000000', 0);
    }
}