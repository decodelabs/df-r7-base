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

        $path = $this->app->getLocalDataPath().'/sass/'.$this->app->envMode;
        $this->_dir = new core\fs\Dir($path);

        if (!$this->_dir->exists()) {
            return;
        }

        $buildId = $this->request['buildId'];
        $done = [];

        foreach ($this->_dir->scanFiles(function ($fileName) {
            return core\uri\Path::extractExtension($fileName) == 'json';
        }) as $fileName => $file) {
            $key = core\uri\Path::extractFileName($fileName);
            $json = $this->data->fromJsonFile($file);
            $sassPath = array_shift($json);

            $shortPath = core\fs\Dir::stripPathLocation($sassPath);
            $activePath = null;

            if ($buildId && strpos($shortPath, 'app://data/local/run/active/') === 0) {
                $activePath = $sassPath;
                $sassPath = str_replace('/data/local/run/active/', '/data/local/build/'.$buildId.'/', $sassPath);
            }


            if (!$this->_checkFile($key, $sassPath, $activePath)) {
                continue;
            }

            if (in_array($sassPath, $done)) {
                continue;
            }

            $done[] = $sassPath;

            $this->io->writeLine($shortPath);
            $bridge = new aura\css\SassBridge($this->context, $sassPath, $activePath);
            $bridge->compile();
        }

        if (empty($done)) {
            $this->io->writeLine('None found');
        }
    }

    protected function _checkFile(string $key, string $sassPath, ?string $activePath)
    {
        $hasBuild = file_exists($this->app->getLocalDataPath().'/run/active/Run.php');
        $delete = !file_exists($sassPath);
        $why = 'file not found';

        if (!$activePath) {
            $activePath = $sassPath;
        }

        $shortPath = core\fs\Dir::stripPathLocation($activePath);

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
