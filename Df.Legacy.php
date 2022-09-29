<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df;

use df;
use df\core;

use DecodeLabs\Glitch;

use Exception;

require_once __DIR__.'/Df.Base.php';

class Launchpad extends LaunchpadBase
{
    // Run
    public static function run(): void
    {
        $parts = explode('/', str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME'])));
        $envId = array_pop($parts);

        if (array_pop($parts) != 'entry') {
            throw new Exception(
                'Entry point does not appear to be valid'
            );
        }

        if (substr($envId, -4) == '.php') {
            $envId = substr($envId, 0, -4);
        }

        $envParts = explode('.', $envId, 2);
        $envId = array_shift($envParts);
        $appPath = implode('/', $parts);

        // Environment
        $startTime = self::initEnvironment();

        // Compilation
        self::initCompilation($appPath);

        // Loaders
        self::initLoaders($appPath, $startTime, true);

        // App
        self::$app = core\app\Base::factory($envId, $appPath);
        Glitch::setRunMode(self::$app->getEnvMode());

        // Run
        self::$app->startup($startTime);
        self::$app->run();

        self::shutdown();
    }

    public static function initEnvironment(): float
    {
        $startTime = microtime(true);

        // Set a few system defaults
        umask(0);
        error_reporting(E_ALL | E_STRICT);
        date_default_timezone_set('UTC');

        if (function_exists('mb_internal_encoding')) {
            mb_internal_encoding('UTF-8');
        }

        return $startTime;
    }

    public static function initCompilation(string $appPath): void
    {
        chdir($appPath.'/entry');

        // Check for compiled version
        $sourceMode = isset($_SERVER['argv']) && (
            in_array('--df-source', $_SERVER['argv']) ||
            in_array('app/build', $_SERVER['argv']) ||
            in_array('app/update', $_SERVER['argv'])
        );

        if (!$sourceMode) {
            $activePath = $appPath.'/data/local/run/active/Run.php';
            $active2Path = $appPath.'/data/local/run/active2/Run.php';

            if (file_exists($activePath)) {
                require $activePath;
            } elseif (file_exists($active2Path)) {
                require $active2Path;
            }
        }

        self::ensureCompileConstants();
    }
}
