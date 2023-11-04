<?php
/**
 * This file is part of the Decode r7 framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Genesis;

use DecodeLabs\Genesis\Bootstrap as Base;

class Bootstrap extends Base
{
    protected string $rootPath;
    protected string $appPath;

    /**
     * Init with root path of source Df.php and app path
     */
    public function __construct(
        string $rootPath,
        string $appPath
    ) {
        $this->rootPath = $rootPath;
        $this->appPath = $appPath;
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

    /**
     * Run found build location
     */
    public function execute(
        string $vendorPath
    ): void {
    }
}
