<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\spur\packaging\bower;

use df;
use df\core;
use df\spur;
use df\flex;
use df\halo;

use DecodeLabs\Atlas;
use DecodeLabs\Systemic;
use DecodeLabs\Glitch;

class Bridge implements IBridge
{
    protected $_installPath = 'assets/lib/vendor';
    protected $_execPath;

    public function __construct()
    {
        $this->_execPath = df\Launchpad::$app->getLocalDataPath().'/bower';
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
            'name' => df\Launchpad::$app->getName(),
            'ignore' => [],
            'dependencies' => $deps
        ]);

        $json2 = flex\Json::toString([
            'directory' => $this->_installPath
        ]);

        Atlas::createFile($this->_execPath.'/bower.json', $json1);
        Atlas::createFile($this->_execPath.'/.bowerrc', $json2);

        return $this;
    }

    public function install(array $deps)
    {
        $this->generate($deps);

        $result = Systemic::$process->newLauncher('bower install')
            ->setWorkingDirectory($this->_execPath)
            ->launch();

        Glitch::incomplete($result);
    }
}
