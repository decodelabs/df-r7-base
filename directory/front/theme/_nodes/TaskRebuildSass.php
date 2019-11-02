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

use DecodeLabs\Terminus\Cli;
use DecodeLabs\Atlas;

class TaskRebuildSass extends arch\node\Task implements arch\node\IBuildTaskNode
{
    const RUN_AFTER = true;

    protected $_dir;

    public function execute()
    {
        $this->ensureDfSource();

        $path = $this->app->getLocalDataPath().'/sass/'.$this->app->envMode;
        $this->_dir = Atlas::$fs->dir($path);

        if (!$this->_dir->exists()) {
            return;
        }

        $buildId = $this->request['buildId'];
        $done = [];

        // Prepare processors
        foreach (aura\css\SassBridge::DEFAULT_PROCESSOR_OPTIONS as $name => $settings) {
            $processor = aura\css\processor\Base::factory($name, $settings);
            $processor->setup(Cli::getSession());
        }


        // Build sass
        foreach ($this->_dir->scanFiles(function ($fileName) {
            return core\uri\Path::extractExtension($fileName) == 'json';
        }) as $fileName => $file) {
            $key = core\uri\Path::extractFileName($fileName);
            $json = $this->data->fromJsonFile((string)$file);
            $sassPath = array_shift($json);

            $shortPath = $this->normalizeSassPath($sassPath);
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

            Cli::{'brightMagenta'}($shortPath.' ');

            $bridge = new aura\css\SassBridge($this->context, $sassPath, $activePath);
            $bridge->setCliSession(Cli::getSession());
            $bridge->compile();

            Cli::success('done');
        }

        if (empty($done)) {
            Cli::success('None found');
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

        $shortPath = $this->normalizeSassPath($activePath);

        if (!$delete && $hasBuild && strpos($shortPath, 'app://data/local/run/active/') !== 0) {
            $delete = true;
            $why = 'generics not required';
        }

        if ($delete) {
            Cli::operative('Skipping '.$shortPath.' - '.$why);
            $exts = ['json', 'css', 'css.map'];

            foreach ($exts as $ext) {
                $this->_dir->deleteFile($key.'.'.$ext);
            }

            return false;
        }

        return true;
    }

    protected function normalizeSassPath(?string $path)
    {
        if (!df\Launchpad::$loader || $path === null) {
            return $path;
        }

        $locations = df\Launchpad::$loader->getLocations();
        $locations['app'] = df\Launchpad::$app->path;
        $path = (string)preg_replace('/[[:^print:]]/', '', $path);

        foreach ($locations as $key => $match) {
            if (substr($path, 0, $len = strlen($match)) == $match) {
                $innerPath = substr(str_replace('\\', '/', $path), $len + 1);

                if (df\Launchpad::$isCompiled && $key == 'root') {
                    $parts = explode('/', $innerPath);
                    array_shift($parts);
                    $innerPath = implode('/', $parts);
                }

                $path = $key.'://'.$innerPath;
                break;
            }
        }

        return $path;
    }
}
