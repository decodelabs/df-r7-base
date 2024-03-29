<?php
/**
 * This file is part of the Decode r7 framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Genesis;

use DecodeLabs\Genesis\Bootstrap as Base;
use Exception;

require_once dirname(__DIR__, 4) . '/decodelabs/genesis/src/Bootstrap.php';

class Bootstrap extends Base
{
    protected string $appPath;

    /**
     * Init with root path of source Df.php and app path
     */
    public function __construct(
        ?string $appPath = null
    ) {
        $this->appPath = $appPath ?? $this->getDefaultAppPath();
    }

    /**
     * Get default app path
     */
    protected function getDefaultAppPath(): string
    {
        if (false === ($entryPath = realpath($_SERVER['SCRIPT_FILENAME']))) {
            throw new Exception(
                'Unable to determine entry point'
            );
        }

        $entryPath = dirname($entryPath);

        if (!str_ends_with($entryPath, '/entry')) {
            throw new Exception(
                'Entry point does not appear to be valid'
            );
        }

        return dirname($entryPath);
    }

    /**
     * Get list of possible build locations
     */
    public function getRootSearchPaths(): array
    {
        // Do we need to force loading source?
        $sourceMode = isset($_SERVER['argv']) && (
            in_array('--df-source', $_SERVER['argv']) ||
            in_array('app/build', $_SERVER['argv']) ||
            in_array('app/update', $_SERVER['argv'])
        );

        if (!$sourceMode) {
            $runPath = $this->appPath . '/data/local/run';

            $paths = [
                $runPath . '/active/Run.php' => $runPath . '/active/apex/vendor',
                $runPath . '/active2/Run.php' => $runPath . '/active2/apex/vendor',
            ];
        } else {
            $paths = [];
        }

        $paths[__FILE__] = $this->appPath . '/vendor';

        return $paths;
    }
}
