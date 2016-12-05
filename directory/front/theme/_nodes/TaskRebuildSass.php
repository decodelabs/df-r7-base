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

    public function execute() {
        $this->io->writeLine('Rebuilding sass...');
        $path = $this->application->getLocalStoragePath().'/sass/'.$this->application->getEnvironmentMode();
        $dir = new core\fs\Dir($path);

        if(!$dir->exists()) {
            return;
        }

        if($this->application->isDevelopment()) {
            $newBuild = false;
        } else {
            $buildId = $this->request['buildId'];
            $newBuild = !empty($buildId);
        }

        $this->io->incrementLineLevel();
        $done = [];

        foreach($dir->scanFiles(function($fileName) {
            return core\uri\Path::extractExtension($fileName) == 'json';
        }) as $fileName => $file) {
            $json = $this->data->jsonDecodeFile($file);
            $sassPath = array_shift($json);

            if($newBuild) {
                $sassPath = preg_replace('|data/local/run/[^/]+/|', 'data/local/run/'.$buildId.'/', $sassPath);
            }

            if(in_array($sassPath, $done)) {
                continue;
            }

            $done[] = $sassPath;

            $sassFile = new core\fs\File($sassPath);
            $shortPath = core\fs\Dir::stripPathLocation($sassFile);

            if(!$sassFile->exists()) {
                $this->io->writeLine('Skipping '.$shortPath.' - file not found');
                $exts = ['json', 'css', 'css.map'];
                $key = core\uri\Path::extractFileName($fileName);

                foreach($exts as $ext) {
                    core\fs\File::delete($path.'/'.$key.'.'.$ext);
                }
                continue;
            }

            $this->io->writeLine($shortPath);
            $bridge = new aura\css\sass\Bridge($this->context, $sassFile);
            $bridge->compile();
        }

        $this->io->decrementLineLevel();
    }
}