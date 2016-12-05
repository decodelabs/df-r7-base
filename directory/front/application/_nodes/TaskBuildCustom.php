<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\application\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;
use df\halo;

class TaskBuildCustom extends arch\node\Task {

    protected $_after = false;
    protected $_buildId;

    public function execute() {
        $this->ensureDfSource();
        $this->_after = isset($this->request['after']);

        if($this->_after) {
            $this->_buildId = $this->request['after'];
        }

        $this->io->writeLine('Running custom user build tasks...');
        $isRun = false;

        foreach($this->_scanNodes() as $request) {
            if(!$isRun) {
                $isRun = true;
            }

            $request = arch\Request::factory($request);

            if($this->_after) {
                $request->query->buildId = $this->_buildId;
            }

            $this->runChild($request);
        }

        if($isRun) {
            $this->io->writeLine();
        }
    }

    protected function _scanNodes() {
        $fileList = df\Launchpad::$loader->lookupFileListRecursive('apex/directory', ['php'], function($path) {
            return basename($path) == '_nodes';
        });

        foreach($fileList as $key => $path) {
            $basename = substr(basename($path), 0, -4);

            if(substr($basename, 0, 4) != 'Task') {
                continue;
            }

            $keyParts = explode('/', dirname($key));
            $class = 'df\\apex\\directory\\'.implode('\\', $keyParts).'\\'.$basename;
            $ref = new \ReflectionClass($class);

            if(!$ref->implementsInterface('df\\arch\\node\\IBuildTaskNode')) {
                continue;
            }

            $runAfter = defined($class.'::RUN_AFTER') && (bool)$class::RUN_AFTER;

            if((!$this->_after && $runAfter)
            || ($this->_after && !$runAfter)) {
                continue;
            }

            array_pop($keyParts);

            if($keyParts[0] == 'front') {
                array_shift($keyParts);
            } else {
                $keyParts[0] = '~'.$keyParts[0];
            }

            yield arch\Request::factory(implode('/', $keyParts).'/'.$this->format->nodeSlug(substr($basename, 4)))->getPath();
        }
    }
}