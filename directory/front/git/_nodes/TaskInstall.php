<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\git\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;
use df\spur;

class TaskInstall extends arch\node\Task
{
    const PACKAGES = [
        'base' => 'git@github.com:decodelabs/df-r7-base.git',
        'nightfire' => 'git@github.com:decodelabs/df-r7-nightfire.git',
        'postal' => 'git@github.com:decodelabs/df-r7-postal.git',
        'spearmint' => 'git@github.com:decodelabs/df-r7-spearmint.git',
        'touchstone' => 'git@github.com:decodelabs/df-r7-touchstone.git',
        'webCore' => 'git@github.com:decodelabs/df-r7-webCore.git'
    ];

    protected $_basePath;
    protected $_setGui = null;

    public function extractCliArguments(core\cli\ICommand $command)
    {
        $args = [];

        foreach ($command->getArguments() as $arg) {
            if (!$arg->isOption()) {
                $args[] = (string)$arg;
            }
        }

        if (isset($args[0]) && $args[0] == 'all') {
            $this->request->query->all = true;
        } elseif (!empty($args)) {
            if (isset($args[1])) {
                $this->request->query->url = array_shift($args);
                $this->request->query->name = array_shift($args);
            } else {
                $name = array_shift($args);

                if (isset(self::PACKAGES[$name])) {
                    $this->request->query->name = $name;
                } else {
                    $this->request->query->url = $name;
                }
            }
        }

        if ($this->request->query->isEmpty()) {
            $this->request->query->all = true;
        }
    }

    public function execute()
    {
        $this->ensureDfSource();

        $this->_basePath = dirname(df\Launchpad::$rootPath);

        if (isset($this->request['all'])) {
            foreach (self::PACKAGES as $name => $url) {
                $this->_cloneRepo($name, $url);
                $this->io->writeLine();
            }
        } else {
            $name = $this->request['name'];
            $url = $this->request['url'];

            if (empty($url)) {
                if (isset(self::PACKAGES[$name])) {
                    $url = self::PACKAGES[$name];
                } else {
                    throw core\Error::EArgument(
                        'No valid repo URL specified'
                    );
                }
            }

            $this->_cloneRepo($name, $url);
        }
    }

    protected function _cloneRepo($name, $url)
    {
        if (empty($name)) {
            $name = (new core\uri\Url($url))->path->getFileName();
        }

        if (is_dir($this->_basePath.'/'.$name)) {
            if (is_dir($this->_basePath.'/'.$name.'/.git/')) {
                $this->io->writeLine($name.' repo already cloned');
            } else {
                $this->io->writeErrorLine('Skipping '.$name.' repo: 3rd party folder already exists');
                return;
            }

            $repo = new spur\vcs\git\Repository($this->_basePath.'/'.$name);
        } else {
            $repo = spur\vcs\git\Repository::createClone($url, $this->_basePath.'/'.$name);
        }

        if ($repo->getConfig('core.filemode')) {
            $this->io->writeLine('Turning off file mode');
            $repo->setConfig('core.filemode', false);
        }

        if ($this->_setGui === null) {
            $this->_setGui = $this->_askBoolean('Would you like to set default GUI config @1020p?');
        }

        if ($this->_setGui) {
            $geometry = '1914x1036+5+23 450 300';
            $this->io->writeLine('Setting geometry to: '.$geometry);

            $repo->setConfig('gui.wmstate', 'zoomed');
            $repo->setConfig('gui.geometry', $geometry);
        }
    }
}
