<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\node;

use df;
use df\core;
use df\spur;
use df\flex;
use df\halo;

class Bridge implements IBridge {

    protected $_nodePath;
    
    public function __construct() {
        $this->_nodePath = df\Launchpad::$application->getLocalStoragePath().'/node';
    }

    public function find($name) {
        return is_file($this->_nodePath.'/node_modules/'.$name.'/package.json');
    }

    public function npmInstall($name) {
        core\fs\Dir::create($this->_nodePath);

        $result = halo\process\Base::newLauncher('npm', [
                'install',
                $name
            ])
            ->setWorkingDirectory($this->_nodePath)
            ->launch();

        if($result->hasError()) {
            throw new RuntimeException($result->getError());
        }

        return $this;
    }

    public function execute($path, $data) {
        core\fs\Dir::create($this->_nodePath);
        core\stub($path);
    }

    public function evaluate($js, $data=null) {
        core\fs\Dir::create($this->_nodePath);

        $payload = flex\json\Codec::encode([
            'js' => $js,
            'data' => $data
        ]);

        if(!is_file($this->_nodePath.'/evaluate.js')) {
            core\fs\File::copy(__DIR__.'/evaluate.js', $this->_nodePath.'/evaluate.js');
        }

        $result = halo\process\Base::newLauncher('node', [
                $this->_nodePath.'/evaluate.js'
            ])
            ->setWorkingDirectory($this->_nodePath)
            ->setGenerator(function() use($payload) {
                return $payload;
            })
            ->launch();

        $output = $result->getOutput();
        
        if($result->hasError() && empty($output)) {
            $error = $result->getError();

            if(!preg_match('/deprecated/i', $error)) {
                throw new RuntimeException($error);
            }
        }

        $output = flex\json\Codec::decode($output);
        return $output['result'];
    }
}