<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\spur\packaging\bower;

use DecodeLabs\Atlas;

use DecodeLabs\Genesis;
use DecodeLabs\Glitch;
use DecodeLabs\Systemic;
use df\flex;

class Bridge implements IBridge
{
    protected $_installPath = 'assets/lib/vendor';
    protected $_execPath;

    public function __construct()
    {
        $this->_execPath = Genesis::$hub->getLocalDataPath() . '/bower';
    }

    public function setInstallPath($path)
    {
        $this->_installPath = $path;
        return $this;
    }

    public function getInstallPath()
    {
        return $this->_installPath;
    }

    public function setExecPath($path)
    {
        $this->_execPath = $path;
        return $this;
    }

    public function getExecPath()
    {
        return $this->_execPath;
    }

    public function generate(array $deps)
    {
        $json1 = flex\Json::toString([
            'name' => Genesis::$hub->getApplicationName(),
            'ignore' => [],
            'dependencies' => $deps
        ]);

        $json2 = flex\Json::toString([
            'directory' => $this->_installPath
        ]);

        Atlas::createFile($this->_execPath . '/bower.json', $json1);
        Atlas::createFile($this->_execPath . '/.bowerrc', $json2);

        return $this;
    }

    public function install(array $deps)
    {
        $this->generate($deps);

        $result = Systemic::capture(
            ['bower', 'install'],
            $this->_execPath
        );

        Glitch::incomplete($result);
    }
}
