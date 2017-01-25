<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex\code\probe;

use df;
use df\core;
use df\flex;
use df\halo;

class Dependencies implements flex\code\IProbe {

    use flex\code\TProbe;

    protected $_errors = [];

    public function probe(flex\code\ILocation $location, $localPath) {
        if(substr($localPath, -4) != '.php') {
            return;
        }

        $pathTest = ['_nodes', '_templates', '.html', '.mail'];

        foreach($pathTest as $test) {
            if(false !== strpos($localPath, $test)) {
                return;
            }
        }

        $dirName = basename(dirname($localPath));

        if($dirName == 'entry') {
            return;
        }

        require_once $location->path.'/'.$localPath;
    }

    public function getErrors() {
        return $this->_errors;
    }

    public function exportTo(flex\code\IProbe $probe) {
        $probe->_errors = array_merge($probe->_errors, $this->_errors);
    }
}