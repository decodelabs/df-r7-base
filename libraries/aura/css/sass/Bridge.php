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

        $this->_workDir = $context->application->getLocalStoragePath().'/sass';

        $this->_isDevelopment = $this->context->application->isDevelopment();
        $this->_key = md5($path);
    }


    public function getHttpResponse() {
        $path = $this->getCompiledPath();

        $output = $this->context->http->fileResponse($path);
        $output->setContentType('text/css');
        $headers = $output->getHeaders();

        if(!$this->_isDevelopment) {
            $headers->setCacheAccess('no-cache')
                ->canStoreCache(false)
                ->shouldRevalidateCache(true);
        } else {
            $headers->setCacheExpiration('+1 year');
        }

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
        core\io\Util::ensureDirExists($this->_workDir.'/'.$this->_key);
        $sourceFiles = [$this->_sourceDir.'/'.$this->_fileName.'.'.$this->_type];
        $manifest = [];

        while(!empty($sourceFiles)) {
            $filePath = array_shift($sourceFiles);
            $fileKey = md5($filePath);
            $fileType = substr($filePath, -4);
            $manifest[$fileKey] = $filePath;

            $contents = file_get_contents($filePath);
            preg_match_all('/\@import \"([^"]+)\"\;/', $contents, $matches);

            if(!empty($matches[1])) {
                $imports = [];

                foreach($matches[1] as $path) {
                    $char = $path{0};

                    if($char == '/') {
                        $importPath = $path;
                    } else if($char == '#') {
                        $importPath = df\Launchpad::$loader->findFile(substr($path, 1));
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
                    $contents = str_replace('@import "'.$import.'"', '@import "'.$keyName.'"', $contents);
                }
            }

            if(!preg_match('/\@charset /', $contents)) {
                $contents = '@charset "UTF-8";'."\n".$contents;
            }

            file_put_contents($this->_workDir.'/'.$this->_key.'/'.$fileKey.'.'.$fileType, $contents);
        }

        file_put_contents($this->_workDir.'/'.$this->_key.'.json', json_encode(array_values($manifest)));

        $path = halo\system\Base::getInstance()->which('sass');

        if(!$path || $path == 'sass') {
            $path = core\Environment::getInstance()->getVendorBinaryPath('sass');
        }

        if(!$path || $path == 'sass' && file_exists('/usr/local/bin/sass')) {
            $path = '/usr/local/bin/sass';
        }

        $result = halo\process\launcher\Base::factory($path, [
                '--compass',
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

        core\io\Util::deleteDir($this->_workDir.'/'.$this->_key);

        return $this->_workDir.'/'.$this->_key.'.css';
    }
}