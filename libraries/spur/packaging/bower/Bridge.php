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

class Bridge implements IBridge {

    protected $_installPath = 'assets/lib/vendor';
    protected $_execPath;

    public function __construct() {
        $this->_execPath = df\Launchpad::$application->getLocalStoragePath().'/bower';
    }

    public function setInstallPath($path) {
        $this->_installPath = $path;
        return $this;
    }

    public function getInstallPath() {
        return $this->_installPath;
    }

    public function setExecPath($path) {
        $this->_execPath = $path;
        return $this;
    }

    public function getExecPath() {
        return $this->_execPath;
    }

    public function generate(array $deps) {
        $application = df\Launchpad::$application;

        $json1 = flex\Json::toString([
            'name' => $application->getName(),
            'ignore' => [],
            'dependencies' => $deps
        ]);

        $json2 = flex\Json::toString([
            'directory' => $this->_installPath
        ]);

        core\fs\File::create($this->_execPath.'/bower.json', $json1);
        core\fs\File::create($this->_execPath.'/.bowerrc', $json2);

        return $this;
    }

    public function install(array $deps) {
        $this->generate($deps);

        $result = halo\process\launcher\Base::factory('bower install')
            ->setWorkingDirectory($this->_execPath)
            ->launch();

        core\dump($result);

        return $this;
    }
}