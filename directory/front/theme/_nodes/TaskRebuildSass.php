<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\theme\_nodes;

use DecodeLabs\Atlas;
use DecodeLabs\Genesis;
use DecodeLabs\Terminus as Cli;

use df\arch;
use df\aura;
use df\core;

class TaskRebuildSass extends arch\node\Task implements arch\node\IBuildTaskNode
{
    public const RUN_AFTER = true;

    protected $_dir;

    public function execute(): void
    {
        $this->ensureDfSource();

        $path = Genesis::$hub->getLocalDataPath() . '/sass/' . Genesis::$environment->getMode();
        $this->_dir = Atlas::dir($path);

        if (!$this->_dir->exists()) {
            return;
        }

        $buildId = $this->request['buildId'];
        $done = [];

        $processorsPrepared = false;


        $runPath = Genesis::$hub->getLocalDataPath() . '/run';
        clearstatcache(true);
        $activeExists = is_file($runPath . '/active/Run.php');
        $active2Exists = is_file($runPath . '/active2/Run.php');


        // Build sass
        foreach ($this->_dir->scanFiles(function ($fileName) {
            return core\uri\Path::extractExtension($fileName) == 'json';
        }) as $fileName => $file) {
            $key = core\uri\Path::extractFileName($fileName);
            $json = $this->data->fromJsonFile((string)$file);
            $sassPath = array_shift($json);

            $shortPath = $this->normalizeSassPath($sassPath);
            $activePath = null;

            if ($buildId) {
                if (
                    strpos($shortPath, 'app://data/local/run/active/') === 0 &&
                    $activeExists
                ) {
                    $sassPath = str_replace('/data/local/run/active/', '/data/local/run/active2/', $sassPath);
                } elseif (
                    strpos($shortPath, 'app://data/local/run/active2/') === 0 &&
                    $active2Exists
                ) {
                    $sassPath = str_replace('/data/local/run/active2/', '/data/local/run/active/', $sassPath);
                }

                $activePath = $sassPath;
                $sassPath = str_replace([
                    '/data/local/run/active/',
                    '/data/local/run/active2/'
                ], '/data/local/build/' . $buildId . '/', $sassPath);

                $shortPath = $this->normalizeSassPath($sassPath);
            }


            if (!$this->_checkFile($key, $sassPath, $activePath)) {
                continue;
            }

            if (in_array($sassPath, $done)) {
                continue;
            }

            $done[] = $sassPath;

            if (!$processorsPrepared) {
                $processorsPrepared = true;

                // Prepare processors
                foreach (aura\css\SassBridge::DEFAULT_PROCESSOR_OPTIONS as $name => $settings) {
                    $processor = aura\css\processor\Base::factory($name, $settings);
                    $processor->setup(Cli::getSession());
                }
            }

            Cli::{'brightMagenta'}($shortPath . ' ');

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
        $hasBuild =
            file_exists(Genesis::$hub->getLocalDataPath() . '/run/active/Run.php') ||
            file_exists(Genesis::$hub->getLocalDataPath() . '/run/active2/Run.php');
        $delete = !file_exists($sassPath);
        $why = 'file not found';

        if (!$activePath) {
            $activePath = $sassPath;
        }

        $shortPath = $this->normalizeSassPath($activePath);

        if (
            !$delete &&
            $hasBuild &&
            strpos($shortPath, 'app://data/local/run/active/') !== 0 &&
            strpos($shortPath, 'app://data/local/run/active2/') !== 0
        ) {
            $delete = true;
            $why = 'generics not required';
        }

        if ($delete) {
            Cli::operative('Skipping ' . $shortPath . ' - ' . $why);
            $exts = ['json', 'css', 'css.map'];

            foreach ($exts as $ext) {
                $this->_dir->deleteFile($key . '.' . $ext);
            }

            return false;
        }

        return true;
    }

    protected function normalizeSassPath(?string $path)
    {
        if ($path === null) {
            return $path;
        }

        $locations = [
            'root' => dirname(Genesis::$build->path),
            'app' => Genesis::$hub->getApplicationPath()
        ];

        $path = (string)preg_replace('/[[:^print:]]/', '', $path);

        foreach ($locations as $key => $match) {
            if (substr($path, 0, $len = strlen($match)) == $match) {
                $innerPath = substr(str_replace('\\', '/', $path), $len + 1);

                if (
                    Genesis::$build->isCompiled() &&
                    $key == 'root'
                ) {
                    $parts = explode('/', $innerPath);
                    array_shift($parts);
                    $innerPath = implode('/', $parts);
                }

                $path = $key . '://' . $innerPath;
                break;
            }
        }

        return $path;
    }
}
