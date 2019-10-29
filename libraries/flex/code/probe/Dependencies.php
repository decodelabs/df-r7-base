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

class Dependencies implements flex\code\IProbe
{
    use flex\code\TProbe;

    protected $_errors = [];
    protected static $_paths = [];

    public function probe(flex\code\Location $location, $localPath)
    {
        if (substr($localPath, -4) != '.php' || isset(self::$_paths[$localPath])) {
            return;
        }

        $pathTest = ['_nodes', '_templates', '.html', '.mail', 'tests', 'vendor'];

        foreach ($pathTest as $test) {
            if (false !== strpos($localPath, $test)) {
                return;
            }
        }

        $dirName = basename(dirname($localPath));

        if ($dirName == 'entry') {
            return;
        }

        self::$_paths[$localPath] = true;
        require_once $location->path.'/'.$localPath;
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
