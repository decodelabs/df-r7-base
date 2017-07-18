<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\theme\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;
use df\flex;
use df\aura;

class TaskRebuildSass extends arch\node\Task implements arch\node\IBuildTaskNode {

    const RUN_AFTER = true;

    protected $_dir;

    public function execute() {
        $this->io->writeLine('Rebuilding sass...');
        $path = $this->app->getLocalDataPath().'/sass/'.$this->app->envMode;
        $this->_dir = new core\fs\Dir($path);

        if(!$this->_dir->exists()) {
            return;
        }

        if($this->app->isDevelopment()) {
            $newBuild = false;
        } else {
            $buildId = $this->request['buildId'];
            $newBuild = !empty($buildId);
        }

        $this->io->indent();
        $done = [];

        foreach($this->_dir->scanFiles(function($fileName) {
            return core\uri\Path::extractExtension($fileName) == 'json';
        }) as $fileName => $file) {
            $key = core\uri\Path::extractFileName($fileName);
            $json = $this->data->fromJsonFile($file);
            $sassPath = array_shift($json);

            $sassFile = new core\fs\File($sassPath);
            $shortPath = core\fs\Dir::stripPathLocation($sassFile);

            $result = $this->_checkFile($sassFile, $key);

            if($newBuild) {
                $sassPath = preg_replace('|data/local/run/[^/]+/|', 'data/local/run/'.$buildId.'/', $sassPath);
                $sassFile = new core\fs\File($sassPath);
                $shortPath = core\fs\Dir::stripPathLocation($sassFile);

                if(!$this->_checkFile($sassFile, $key)) {
                    continue;
                }
            } else if(!$result) {
                continue;
            }

            if(in_array($sassPath, $done)) {
                continue;
            }

            $done[] = $sassPath;

            $this->io->writeLine($shortPath);
            $bridge = new aura\css\SassBridge($this->context, $sassFile);
            $bridge->compile();
        }

        $this->io->outdent();
    }

    protected function _checkFile($file, $key) {
        $shortPath = core\fs\Dir::stripPathLocation($file);

        if(!$file->exists()) {
            $this->io->writeLine('Skipping '.$shortPath.' - file not found');
            $exts = ['json', 'css', 'css.map'];

            foreach($exts as $ext) {
                $this->_dir->deleteFile($key.'.'.$ext);
            }

            return false;
        }

        return true;
    }
}
