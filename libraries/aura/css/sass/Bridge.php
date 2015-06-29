<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\css\sass;

use df;
use df\core;
use df\aura;
use df\arch;
use df\link;
use df\halo;
use df\flex;

class Bridge implements IBridge {
    
    public $context;

    protected $_fileName;
    protected $_type;
    protected $_sourceDir;
    protected $_workDir;
    protected $_key;
    protected $_isDevelopment;

    public function __construct(arch\IContext $context, $path) {
        $this->context = $context;
        $path = realpath($path);

        if(!is_file($path)) {
            throw new RuntimeException(
                'Sass file not found'
            );
        }

        $this->_sourceDir = dirname($path);
        $basename = basename($path);

        $this->_fileName = substr($basename, 0, -5);
        $this->_type = strtolower(substr($basename, -4));

        $this->_workDir = $context->application->getLocalStoragePath().'/sass/'.$context->application->getEnvironmentMode();

        $this->_isDevelopment = $this->context->application->isDevelopment();
        $this->_key = md5($path);
    }


    public function getHttpResponse() {
        $path = $this->getCompiledPath();

        $output = $this->context->http->fileResponse($path);
        $output->setContentType('text/css');
        $headers = $output->getHeaders();

        if($this->_isDevelopment) {
            $headers->setCacheAccess('no-cache')
                ->canStoreCache(false)
                ->shouldRevalidateCache(true);
        } else {
            $headers->setCacheExpiration('+1 year');
        }

        return $output;
    }

    public function getMapHttpResponse() {
        $path = $this->getCompiledPath().'.map';

        $output = $this->context->http->fileResponse($path);
        $output->setContentType('application/json');
        $headers = $output->getHeaders();

        $headers->setCacheAccess('no-cache')
            ->canStoreCache(false)
            ->shouldRevalidateCache(true);

        return $output;
    }

    public function getCompiledPath() {
        $filePath = $this->_workDir.'/'.$this->_key.'.css';

        if(!is_file($filePath)) {
            return $this->compile();
        }

        $mtime = filemtime($filePath);

        if($this->_isDevelopment) {
            $manifestPath = $this->_workDir.'/'.$this->_key.'.json';

            if(!is_file($manifestPath)) {
                $this->compile();
            } else {
                $files = json_decode(file_get_contents($manifestPath), true);

                foreach($files as $file) {
                    if(!is_file($file) || $mtime < filemtime($file)) {
                        $this->compile();
                        break;
                    }
                }
            }
        } else {
            if($mtime < df\Launchpad::COMPILE_TIMESTAMP) {
                $this->compile();
            }
        }

        return $filePath;
    }

    public function compile() {
        core\fs\Dir::create($this->_workDir.'/'.$this->_key);
        $sourceFiles = [$this->_sourceDir.'/'.$this->_fileName.'.'.$this->_type];
        $manifest = [];

        while(!empty($sourceFiles)) {
            $filePath = array_shift($sourceFiles);
            $fileKey = md5($filePath);
            $fileType = substr($filePath, -4);
            $manifest[$fileKey] = $filePath;

            $contents = file_get_contents($filePath);
            $contents = $this->_replaceLocalPaths($contents, $filePath, $sourceFiles);
            $contents = $this->_replaceUrls($contents);
            $contents = $this->_setCharset($contents);

            file_put_contents($this->_workDir.'/'.$this->_key.'/'.$fileKey.'.'.$fileType, $contents);
        }

        $options = [];
        $jsonPath = $this->_sourceDir.'/'.$this->_fileName.'.'.$this->_type.'.json';

        if(is_file($jsonPath)) {
            $manifest[md5($jsonPath)] = $jsonPath;
            $options = flex\json\Codec::decode(file_get_contents($this->_sourceDir.'/'.$this->_fileName.'.'.$this->_type.'.json'));
        }

        $path = halo\system\Base::getInstance()->which('sass');

        if(!$path || $path == 'sass') {
            $path = core\Environment::getInstance()->getBinaryPath('sass');
        }

        if(!$path || $path == 'sass' && file_exists('/usr/local/bin/sass')) {
            $path = '/usr/local/bin/sass';
        }

        $envMode = $this->context->application->getEnvironmentMode();

        switch($envMode) {
            case 'development':
                $outputType = 'expanded';
                break;

            case 'testing':
                $outputType = 'compact';
                break;

            case 'production':
                $outputType = 'compressed';
                break;
        }


        $result = halo\process\launcher\Base::factory($path, [
                '--compass',
                '--style='.$outputType,
                '--sourcemap=file',
                $this->_workDir.'/'.$this->_key.'/'.$this->_key.'.'.$this->_type, 
                $this->_workDir.'/'.$this->_key.'.css'
            ])
            ->setWorkingDirectory($this->_workDir)
            ->launch();

        if($result->hasError()) {
            throw new RuntimeException(
                $result->getError()
            );
        }

        $output = $result->getOutput();

        if(false !== stripos($output, 'error')) {
            throw new RuntimeException(
                $output
            );
        }

        $content = file_get_contents($this->_workDir.'/'.$this->_key.'.css');

        // Replace map url
        $mapPath = $this->_workDir.'/'.$this->_key.'.css.map';
        $mapExists = is_file($mapPath);

        $content = str_replace(
            '/*# sourceMappingURL='.$this->_key.'.css.map */',
            $mapExists && $envMode !== 'production' ?
                '/*# sourceMappingURL='.$this->_fileName.'.'.$this->_type.'.map */' :
                '',
            $content
        );

        file_put_contents($this->_workDir.'/'.$this->_key.'.css', $content);

        // Replace map file paths
        if($mapExists && $envMode != 'production') {
            $content = file_get_contents($mapPath);

            foreach($manifest as $fileKey => $filePath) {
                $content = str_replace(
                    'file://'.$this->_workDir.'/'.$this->_key.'/'.$fileKey.'.'.$this->_type, 
                    'file://'.$filePath, 
                    $content
                );
            }

            file_put_contents($this->_workDir.'/'.$this->_key.'.css.map', $content);
        }

        // Apply plugins
        if(!empty($options)) {
            foreach($options as $name => $settings) {
                $this->_applyProcessor($name, $settings);
            }
        }

        file_put_contents($this->_workDir.'/'.$this->_key.'.json', json_encode(array_values($manifest)));

        // Clean up
        core\fs\Dir::delete($this->_workDir.'/'.$this->_key);

        return $this->_workDir.'/'.$this->_key.'.css';
    }

    protected function _replaceLocalPaths($sass, $filePath, array &$sourceFiles) {
        preg_match_all('/\@import \"([^"]+)\"\;/', $sass, $matches);

        if(!empty($matches[1])) {
            $imports = [];

            foreach($matches[1] as $path) {
                if($path{0} == '/') {
                    $importPath = $path;
                } else if(false !== strpos($path, '://')) {
                    $importPath = $this->_uriToPath($path);
                } else {
                    $importPath = realpath(dirname($filePath).'/'.$path);
                }

                if(empty($importPath)) {
                    continue;
                }

                $key = md5($importPath);
                $type = substr($importPath, -4);
                $imports[$path] = $key.'.'.$type;

                if(!isset($manifest[$key])) {
                    $sourceFiles[] = $importPath;
                }
            }

            foreach($imports as $import => $keyName) {
                $sass = str_replace('@import "'.$import.'"', '@import "'.$keyName.'"', $sass);
            }
        }

        return $sass;
    }

    protected function _replaceUrls($sass) {
        preg_match_all('/url\([\'\"]?([^\'\"\)]+)[\'\"]?\)/i', $sass, $matches);

        if(df\Launchpad::COMPILE_TIMESTAMP) {
            $cts = df\Launchpad::COMPILE_TIMESTAMP;
        } else if($this->context->application->isDevelopment()) {
            $cts = time();
        } else {
            $cts = null;
        }

        if(!empty($matches[1])) {
            $urls = [];

            foreach($matches[1] as $i => $path) {
                if($cts !== null && $path{0} == '.') {
                    $separator = false !== strpos($path, '?') ? '&' : '?';
                    $urls[$matches[0][$i]] = $path.$separator.'cts='.$cts;
                } else if(false !== strpos($path, '://')) {
                    $urls[$matches[0][$i]] = $this->context->uri($path);
                }
            }

            foreach($urls as $match => $url) {
                $sass = str_replace($match, 'url(\''.$url.'\')', $sass);
            }
        }

        return $sass;
    }

    protected function _setCharset($sass) {
        if(!preg_match('/\@charset /', $sass)) {
            $sass = '@charset "UTF-8";'."\n".$sass;
        }

        return $sass;
    }

    protected function _uriToPath($uri) {
        $parts = explode('://', $uri);
        $schema = array_shift($parts);
        $path = array_shift($parts);

        switch($schema) {
            case 'apex':
                if($output = df\Launchpad::$loader->findFile('apex/'.$path)) {
                    return $output;
                }

                break;

            case 'asset':
                if($output = df\Launchpad::$loader->findFile('apex/assets/'.$path)) {
                    return $output;
                }

                break;

            case 'theme':
                $theme = $this->context->extractThemeId($path);

                if(!$theme) {
                    $theme = $this->context->apex->getTheme()->getId();
                }

                $output = df\Launchpad::$loader->findFile('apex/themes/'.$theme.'/assets/'.$path);

                if(!$output) {
                    $output = df\Launchpad::$loader->findFile('apex/themes/shared/assets/'.$path);
                }
                
                if($output) {
                    return $output;
                }

                break;
        }

        return $uri;
    }

    protected function _applyProcessor($name, array $settings) {
        $processor = aura\css\processor\Base::factory($name, $settings);
        $processor->process($this->_workDir.'/'.$this->_key.'.css');
    }
}