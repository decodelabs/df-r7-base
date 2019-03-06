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

class TaskRebuildSass extends arch\node\Task implements arch\node\IBuildTaskNode
{
    const RUN_AFTER = true;

    protected $_dir;

    public function execute()
    {
        $this->ensureDfSource();

        $this->io->writeLine('Rebuilding sass...');
        $path = $this->app->getLocalDataPath().'/sass/'.$this->app->envMode;
        $this->_dir = new core\fs\Dir($path);

        if (!$this->_dir->exists()) {
            return;
        }

        $this->io->indent();
        $done = [];

        foreach ($this->_dir->scanFiles(function ($fileName) {
            return core\uri\Path::extractExtension($fileName) == 'json';
        }) as $fileName => $file) {
            $key = core\uri\Path::extractFileName($fileName);
            $json = $this->data->fromJsonFile($file);
            $sassPath = array_shift($json);

            $sassFile = new core\fs\File($sassPath);
            $shortPath = core\fs\Dir::stripPathLocation($sassFile);

            if (!$this->_checkFile($sassFile, $key)) {
                continue;
            }

            if (in_array($sassPath, $done)) {
                continue;
            }

            $done[] = $sassPath;

            $this->io->writeLine($shortPath);
            $bridge = new aura\css\SassBridge($this->context, $sassFile);
            $bridge->compile();
        }

        $this->io->outdent();
    }

    protected function _checkFile($file, $key)
    {
        $shortPath = core\fs\Dir::stripPathLocation($file);
        $hasBuild = file_exists($this->app->getLocalDataPath().'/run/active/Run.php');
        $delete = !$file->exists();
        $why = 'file not found';

        if (!$delete && $hasBuild && strpos($shortPath, 'app://data/local/run/active/') !== 0) {
            $delete = true;
            $why = 'generics not required';
        }

        if ($delete) {
            $this->io->writeLine('Skipping '.$shortPath.' - '.$why);
            $exts = ['json', 'css', 'css.map'];

            foreach ($exts as $ext) {
                $this->_dir->deleteFile($key.'.'.$ext);
            }

            return false;
        }

        return true;
    }
}
