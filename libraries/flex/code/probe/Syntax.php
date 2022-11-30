<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\flex\code\probe;

use DecodeLabs\Systemic;

use df\flex;

class Syntax implements flex\code\IProbe
{
    use flex\code\TProbe;

    protected $_errors = [];

    public function probe(flex\code\Location $location, $localPath)
    {
        if (substr($localPath, -4) != '.php') {
            return;
        }

        $result = Systemic::capture(['php', '-l', $location->path . '/' . $localPath]);
        $result = trim($result->getOutput());
        $lines = explode("\n", $result);
        $result = (string)array_shift($lines);

        if (strpos($result, 'No syntax errors detected') === false) {
            $this->_errors[$location->id . '://' . $localPath] = $result;
        }
    }

    public function setErrors(array $errors)
    {
        $this->_errors = $errors;
        return $this;
    }

    public function getErrors(): array
    {
        return $this->_errors;
    }

    public function exportTo(flex\code\IProbe $probe)
    {
        if (!$probe instanceof self) {
            return;
        }

        $probe->setErrors(array_merge(
            $probe->getErrors(),
            $this->_errors
        ));
    }
}
