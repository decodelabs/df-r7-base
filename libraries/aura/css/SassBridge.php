<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\aura\css;

use DecodeLabs\Atlas;
use DecodeLabs\Exceptional;
use DecodeLabs\Genesis;
use DecodeLabs\Glitch;
use DecodeLabs\R7\Legacy;

use DecodeLabs\Systemic;
use DecodeLabs\Terminus\Session;
use df\arch;
use df\aura;
use df\core;
use df\flex;
use df\link;

class SassBridge implements ISassBridge
{
    public const DEFAULT_PROCESSOR_OPTIONS = [
        'autoprefixer' => []
    ];

    public $context;

    protected $_fileName;
    protected $_type;
    protected $_sourceDir;
    protected $_activeDir;
    protected $_workDir;
    protected $_key;
    protected $_isDevelopment;

    protected $_manifest = [];
    protected $_session;

    public function __construct(arch\IContext $context, string $path, string $activePath = null)
    {
        $this->context = $context;
        $path = realpath($path);

        if ($path === false || !is_file($path)) {
            throw Exceptional::NotFound([
                'message' => 'Sass file not found',
                'data' => $path
            ]);
        }

        if ($activePath) {
            $this->_activeDir = dirname($activePath);
        }

        $this->_sourceDir = dirname($path);
        $basename = basename($path);

        $this->_fileName = substr($basename, 0, -5);
        $this->_type = strtolower(substr($basename, -4));

        $this->_workDir = Genesis::$hub->getLocalDataPath() . '/sass/' . Genesis::$environment->getMode();

        $this->_isDevelopment = Genesis::$environment->isDevelopment();
        $this->_key = md5($activePath ?? $path);
    }


    public function setCliSession(?Session $session)
    {
        $this->_session = $session;
        return $this;
    }

    public function getCliSession(): ?Session
    {
        return $this->_session;
    }

    public function getHttpResponse(): link\http\IResponse
    {
        $path = $this->getCompiledPath();

        $output = Legacy::$http->fileResponse($path);
        $output->setContentType('text/css');
        $headers = $output->getHeaders();

        if ($this->_isDevelopment) {
            $headers->setCacheAccess('no-cache')
                ->canStoreCache(false)
                ->shouldRevalidateCache(true);
        } else {
            $headers->setCacheExpiration('+1 year');
        }

        return $output;
    }

    public function getMapHttpResponse(): link\http\IResponse
    {
        $path = $this->getCompiledPath() . '.map';

        $output = Legacy::$http->fileResponse($path);
        $output->setContentType('application/json');
        $headers = $output->getHeaders();

        $headers->setCacheAccess('no-cache')
            ->canStoreCache(false)
            ->shouldRevalidateCache(true);

        return $output;
    }

    public function getCompiledPath(): string
    {
        $filePath = $this->_workDir . '/' . $this->_key . '.css';

        if (!is_file($filePath)) {
            $this->compile();
            return $filePath;
        }

        $mtime = filemtime($filePath);

        if ($this->_isDevelopment) {
            $manifestPath = $this->_workDir . '/' . $this->_key . '.json';

            if (!is_file($manifestPath)) {
                $this->compile();
            } else {
                if (false === ($manifestData = file_get_contents($manifestPath))) {
                    throw Exceptional::Runtime(
                        'Unable to read manifest data',
                        null,
                        $manifestPath
                    );
                }

                $files = json_decode($manifestData, true);

                foreach ($files as $file) {
                    if (!is_file($file) || $mtime < filemtime($file)) {
                        $this->compile();
                        break;
                    }
                }
            }
        } else {
            if (
                Genesis::$build->isCompiled() &&
                $mtime < Genesis::$build->getTime() - 30
            ) {
                $this->compile(true);
            }
        }

        return $filePath;
    }

    public function compile(bool $doNotWait = false): void
    {
        $lockFile = new LockFile($this->_workDir, 60);
        $lockFile->setFileName($this->_key . '.lock');

        if ($this->_waitForLock($lockFile, $doNotWait)) {
            return;
        }

        $lockFile->lock();
        Atlas::createDir($this->_workDir . '/' . $this->_key);

        $error = null;

        try {
            $this->_compile();
        } catch (\Throwable $e) {
            $error = $e;
        }

        $lockFile->unlock();
        Atlas::deleteDir($this->_workDir . '/' . $this->_key);

        if ($error) {
            throw $error;
        }

        return;
    }

    protected function _waitForLock(LockFile $lockFile, bool $doNotWait = false)
    {
        if ($lockFile->canLock()) {
            return false;
        }

        $filePath = $this->_workDir . '/' . $this->_key . '.css';

        if ($doNotWait && is_file($filePath)) {
            return true;
        }

        do {
            sleep(1);
        } while (
            /** @phpstan-ignore-next-line */
            !$lockFile->canLock()
        );

        /** @phpstan-ignore-next-line */
        return is_file($filePath);
    }

    protected function _compile()
    {
        $envMode = Genesis::$environment->getMode();
        $envId = Genesis::$environment->getName();

        $sourceFiles = [$this->_sourceDir . '/' . $this->_fileName . '.' . $this->_type];
        $this->_manifest = [];
        $first = true;
        $mainFileKey = $this->_key;

        while (!empty($sourceFiles)) {
            $filePath = array_shift($sourceFiles);

            if ($this->_activeDir) {
                $activePath = str_replace($this->_sourceDir, $this->_activeDir, $filePath);
            } else {
                $activePath = $filePath;
            }

            $fileKey = md5($filePath);

            if (isset($this->_manifest[$fileKey])) {
                continue;
            }

            $fileType = substr($filePath, -4);
            $this->_manifest[$fileKey] = $activePath;

            $contents = file_get_contents($filePath);
            $contents = $this->_replaceLocalPaths($contents, $filePath, $sourceFiles);
            $contents = $this->_replaceUrls($contents);
            $contents = $this->_setCharset($contents);

            if ($first) {
                $mainFileKey = $fileKey;
                $first = false;
                $contents =
                    '$env-mode: \'' . $envMode . '\';' . "\n" .
                    '$env-id: \'' . $envId . '\';' . "\n" .
                    $contents;
            }

            file_put_contents($this->_workDir . '/' . $this->_key . '/' . $fileKey . '.' . basename($filePath), $contents);
        }

        $options = [];
        $jsonPath = $this->_sourceDir . '/' . $this->_fileName . '.' . $this->_type . '.json';

        if (is_file($jsonPath)) {
            $this->_manifest[md5($jsonPath)] = $jsonPath;
            $options = flex\Json::fromFile($this->_sourceDir . '/' . $this->_fileName . '.' . $this->_type . '.json');
        } else {
            $options = self::DEFAULT_PROCESSOR_OPTIONS;
        }

        $path = Systemic::$os->which('sassc');

        if (!$path || $path == 'sassc') {
            $path = Systemic::$os->which('sass');

            if (!$path || $path == 'sass') {
                $path = core\environment\Config::getInstance()->getBinaryPath('sass');
            }

            if (!$path || $path == 'sass' && file_exists('/usr/local/bin/sass')) {
                $path = '/usr/local/bin/sass';
            }
        }

        $isC = basename($path) == 'sassc';

        switch ($envMode) {
            case 'development':
                $outputType = 'expanded';
                break;

            case 'testing':
                $outputType = 'compact';
                break;

            case 'production':
            default:
                $outputType = 'compressed';
                break;
        }

        if ($isC) {
            $args = [
                '--style=' . $outputType,
                '--sourcemap'
            ];
        } else {
            $args = [
                '--quiet',
                '--style=' . $outputType,
                '--sourcemap=file',
                '-Eutf-8'
            ];
        }

        $args[] = $this->_workDir . '/' . $this->_key . '/' . $mainFileKey . '.' . $this->_fileName . '.' . $this->_type;
        $args[] = $this->_workDir . '/' . $this->_key . '/' . $this->_key . '.css';

        $result = Systemic::capture(
            [$path, ...$args],
            $this->_workDir
        );

        $output = $result->getOutput();

        if ($result->hasError()) {
            $error = Exceptional::Runtime(
                $result->getError()
            );

            if (!empty($output)) {
                Glitch::logException($error);
            } else {
                throw $error;
            }
        }


        if (false !== stripos((string)$output, 'error')) {
            throw Exceptional::Runtime(
                $output
            );
        }


        // Apply plugins
        if (!empty($options)) {
            foreach ($options as $name => $settings) {
                $processor = aura\css\processor\Base::factory($name, $settings);
                $processor->setup($this->_session);
                $processor->process($this->_workDir . '/' . $this->_key . '/' . $this->_key . '.css', $this->_session);
            }
        }

        $cssFilePath = $this->_workDir . '/' . $this->_key . '/' . $this->_key . '.css';

        if (false === ($content = file_get_contents($cssFilePath))) {
            throw Exceptional::Runtime(
                'Unable to read temp css file',
                null,
                $cssFilePath
            );
        }

        // Replace map url
        $mapPath = $this->_workDir . '/' . $this->_key . '/' . $this->_key . '.css.map';
        $mapExists = is_file($mapPath);

        $content = str_replace(
            '/*# sourceMappingURL=' . $this->_key . '.css.map */',
            $mapExists && $envMode !== 'production' ?
                '/*# sourceMappingURL=' . $this->_fileName . '.' . $this->_type . '.map */' :
                '',
            $content
        );

        file_put_contents($this->_workDir . '/' . $this->_key . '/' . $this->_key . '.css', $content);


        // Replace map file paths
        if ($mapExists && $envMode != 'production') {
            if (false === ($content = file_get_contents($mapPath))) {
                throw Exceptional::Runtime(
                    'Unable to read map file',
                    null,
                    $mapPath
                );
            }

            $content = str_replace(
                $this->_key . '.css',
                $this->_fileName . '.css',
                $content
            );

            foreach ($this->_manifest as $fileKey => $filePath) {
                $content = str_replace(
                    [
                        'file://' . $this->_workDir . '/' . $this->_key . '/' . $fileKey . '.' . basename($filePath),
                        $fileKey . '.' . basename($filePath)
                    ],
                    'file://' . $filePath,
                    $content
                );
            }

            file_put_contents($this->_workDir . '/' . $this->_key . '/' . $this->_key . '.css.map', $content);
        }


        // Write Manifest json
        file_put_contents(
            $this->_workDir . '/' . $this->_key . '/' . $this->_key . '.json',
            json_encode(array_values($this->_manifest), \JSON_UNESCAPED_SLASHES)
        );

        $files = [
            $this->_key . '.css',
            $this->_key . '.css.map',
            $this->_key . '.json'
        ];

        foreach ($files as $fileName) {
            Atlas::copyFile($this->_workDir . '/' . $this->_key . '/' . $fileName, $this->_workDir . '/' . $fileName);
        }

        return;
    }

    protected function _replaceLocalPaths($sass, $filePath, array &$sourceFiles)
    {
        $sass = preg_replace('/\@import \'([^\']+)\'/', '@import "$1"', $sass);
        preg_match_all('/\@import \"([^"]+)\"/', $sass, $matches);

        if (!empty($matches[1])) {
            $imports = [];

            foreach ($matches[1] as $path) {
                $activePath = $path;

                if (!preg_match('/\.s(a|c)ss$/i', $activePath)) {
                    $activePath .= '.' . $this->_type;
                }

                if ($path[0] == '/') {
                    $importPath = $activePath;
                } elseif (false !== strpos($activePath, '://')) {
                    $importPath = $this->_uriToPath($activePath);
                } else {
                    $importPath = realpath(dirname($filePath) . '/' . $activePath);
                }

                if (empty($importPath)) {
                    continue;
                }

                $key = md5($importPath);
                $type = substr($importPath, -4);
                $imports[$path] = $key . '.' . basename($path);

                if (!isset($this->_manifest[$key])) {
                    $sourceFiles[] = $importPath;
                }
            }

            foreach ($imports as $import => $keyName) {
                $sass = str_replace('@import "' . $import . '"', '@import "' . $keyName . '"', $sass);
            }
        }

        return $sass;
    }

    protected function _replaceUrls($sass)
    {
        $cts = Genesis::$build->getCacheBuster();
        preg_match_all('/url\([\'\"]?([^\'\"\)]+)[\'\"]?\)/i', $sass, $matches);

        if (!empty($matches[1])) {
            $urls = [];

            foreach ($matches[1] as $i => $path) {
                if ($cts !== null && $path[0] == '.') {
                    $separator = false !== strpos($path, '?') ? '&' : '?';
                    $urls[$matches[0][$i]] = $path . $separator . 'cts=' . $cts;
                } elseif (false !== strpos($path, '://')) {
                    $urls[$matches[0][$i]] = $this->context->uri($path);
                }
            }

            foreach ($urls as $match => $url) {
                $sass = str_replace((string)$match, 'url(\'' . $url . '\')', $sass);
            }
        }

        $sass = preg_replace('/(\?|\&)cts=([^0-9])/i', '$1cts=' . $cts . '$2', $sass);

        return $sass;
    }

    protected function _setCharset($sass)
    {
        if (!preg_match('/\@charset /', $sass)) {
            $sass = '@charset "UTF-8";' . "\n" . $sass;
        }

        return $sass;
    }

    protected function _uriToPath($uri)
    {
        $parts = explode('://', $uri);
        $schema = array_shift($parts);
        $path = array_shift($parts);

        switch ($schema) {
            case 'apex':
                if ($output = Legacy::getLoader()->findFile('apex/' . $path)) {
                    return $output;
                }

                break;

            case 'asset':
                if ($output = Legacy::getLoader()->findFile('apex/assets/' . $path)) {
                    return $output;
                }

                break;

            case 'theme':
                $theme = $this->context->extractThemeId($path);

                if (!$theme) {
                    $theme = $this->context->apex->getTheme()->getId();
                }

                $output = Legacy::getLoader()->findFile('apex/themes/' . $theme . '/assets/' . $path);

                if (!$output) {
                    $output = Legacy::getLoader()->findFile('apex/themes/shared/assets/' . $path);
                }

                if ($output) {
                    return $output;
                } else {
                    throw Exceptional::NotFound(
                        'Theme sass file not found: ' . $path
                    );
                }
        }

        return $uri;
    }
}
