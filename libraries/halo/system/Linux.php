<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\system;

use df;
use df\core;
use df\halo;

class Linux extends Unix {
    
    private static $_distributions = array(
        'Debian' => array('/etc/debian_release', '/etc/debian_version'),
        'SuSE' => array('/etc/SuSE-release', '/etc/UnitedLinux-release'),
        'Mandrake' => '/etc/mandrake-release',
        'Gentoo' => '/etc/gentoo-release',
        'Fedora' => '/etc/fedora-release',
        'RedHat' => array('/etc/redhat-release', '/etc/redhat_version'),
        'Slackware' => array('/etc/slackware-release', '/etc/slackware-version'),
        'Trustix' => array('/etc/trustix-release', '/etc/trustix-version'),
        'FreeEOS' => '/etc/eos-version',
        'Arch' => '/etc/arch-release',
        'Cobalt' => '/etc/cobalt-release',
        'LFS' => '/etc/lfs-release',
        'Rubix' => '/etc/rubix-release',
        'Ubuntu' => '/etc/lsb-release',
        'PLD' => '/etc/pld-release',
        'HLFS' => '/etc/hlfs-release',
        'Synology' => '/etc/synoinfo.conf'
    );
    
    protected $_osDistribution;
    
    public function getOSDistribution() {
        if($this->_osDistribution === null) {
            $this->_osDistribution = $this->_lookupOSDistribution();
        }
        
        return $this->_osDistribution;
    }
    
    private function _lookupOSDistribution() {
        $result = halo\process\Base::launch('lsb_release', '-a');
        
        if($result->hasOutput()) {
            $lines = explode("\n", $result->getOutput());
            
            foreach($lines as $line) {
                $parts = explode(':', $line, 2);
                $key = trim(array_shift($parts));
                
                if($key == 'Description') {
                    return trim(array_shift($parts));
                }
            }
        }
        
        
        foreach(self::$_distributions as $name => $files) {
            if(!is_array($files)) {
                $files = array($files);
            }
            
            foreach($files as $file) {
                if(file_exists($file)) {
                    return $name;
                }
            }
        }
        
        return 'Unknown';
    }
}