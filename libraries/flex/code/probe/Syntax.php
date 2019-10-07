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

use DecodeLabs\Systemic;

class Syntax implements flex\code\IProbe
{
    use flex\code\TProbe;

    protected $_errors = [];

    public function probe(flex\code\ILocation $location, $localPath)
    {
        if (substr($localPath, -4) != '.php') {
            return;
        }

        $result = Systemic::$process->launch('php', ['-l', $location->path.'/'.$localPath]);
        $result = trim($result->getOutput());
        $lines = explode("\n", $result);
        $result = array_shift($lines);

        if (strpos($result, 'No syntax errors detected') === false) {
            $this->_errors[$location->id.'://'.$localPath] = $result;
        }
    }

    public function getErrors()
    {
        return $this->_errors;
    }

    public function exportTo(flex\code\IProbe $probe)
    {
        $probe->_errors = array_merge($probe->_errors, $this->_errors);
    }
}
