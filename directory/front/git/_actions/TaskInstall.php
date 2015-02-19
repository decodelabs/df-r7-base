<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\git\_actions;

use df;
use df\core;
use df\apex;
use df\arch;
use df\spur;

class TaskInstall extends arch\task\Action {

    protected static $_packages = [
        'base' => 'git@github.com:decodelabs/df-r7-base.git',
        'interact' => 'git@github.com:decodelabs/df-r7-interact.git',
        'mailchimp' => 'git@github.com:decodelabs/df-r7-mailchimp.git',
        'media' => 'git@github.com:decodelabs/df-r7-media.git',
        'nightfire' => 'git@github.com:decodelabs/df-r7-nightfire.git',
        'nightfireCore' => 'git@github.com:decodelabs/df-r7-nightfireCore.git',
        'postal' => 'git@github.com:decodelabs/df-r7-postal.git',
        'touchstone' => 'git@github.com:decodelabs/df-r7-touchstone.git',
        'webCore' => 'git@github.com:decodelabs/df-r7-webCore.git',
        'webStats' => 'git@github.com:decodelabs/df-r7-webStats.git'
    ];

    protected $_basePath;
    protected $_setGui = null;

    public function extractCliArguments(core\cli\ICommand $command) {
        $args = [];

        foreach($command->getArguments() as $arg) {
            if(!$arg->isOption()) {
                $args[] = (string)$arg;
            }
        }

        if(isset($args[0]) && $args[0] == 'all') {
            $this->request->query->all = true;
        } else {
            if(isset($args[1])) {
                $this->request->query->url = array_shift($args);
                $this->request->query->name = array_shift($args);
            } else {
                $name = array_shift($args);
                
                if(isset(self::$_packages[$name])) {
                    $this->request->query->name = $name;
                } else {
                    $this->request->query->url = $name;
                }
            }
        }
    }

    public function execute() {
        if(df\Launchpad::IS_COMPILED) {
            $this->throwError(500, 'Cannot execute git commands on compiled version of app');
        }

        $this->_basePath = dirname(df\Launchpad::DF_PATH);

        if(isset($this->request->query->all)) {
            foreach(self::$_packages as $name => $url) {
                $this->_cloneRepo($name, $url);
                $this->io->writeLine();
            }
        } else {
            $name = $this->request->query['name'];
            $url = $this->request->query['url'];

            if(empty($url)) {
                if(isset(self::$_packages[$name])) {
                    $url = self::$_packages[$name];
                } else {
                    $this->throwError(500, 'No valid repo URL specified');
                }
            }

            $this->_cloneRepo($name, $url);
        }
    }

    protected function _cloneRepo($name, $url) {
        if(empty($name)) {
            $name = (new core\uri\Url($url))->path->getFileName();
        }

        if(is_dir($this->_basePath.'/'.$name)) {
            if(is_dir($this->_basePath.'/'.$name.'/.git/')) {
                $this->io->writeLine($name.' repo already cloned');
            } else {
                $this->io->writeErrorLine('Skipping '.$name.' repo: 3rd party folder already exists');
                return;
            }

            $repo = new spur\vcs\git\Repository($this->_basePath.'/'.$name);
        } else {
            $repo = spur\vcs\git\Repository::createClone($url, $this->_basePath.'/'.$name);
        }

        if($repo->getConfig('core.filemode')) {
            $this->io->writeLine('Turning off file mode');
            $repo->setConfig('core.filemode', false);
        }
        
        if($this->_setGui === null) {
            $this->io->write('>> Would you like to set default GUI config @1020p? [N/y] ');
            $answer = trim($this->io->readLine());
            $this->_setGui = $this->format->stringToBoolean($answer, false);
        }

        if($this->_setGui) {
            $geometry = '1914x1036+5+23 450 300';
            $this->io->writeLine('Setting geometry to: '.$geometry);

            $repo->setConfig('gui.wmstate', 'zoomed');
            $repo->setConfig('gui.geometry', $geometry);
        }
    }
}