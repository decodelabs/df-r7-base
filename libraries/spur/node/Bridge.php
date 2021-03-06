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

use DecodeLabs\Atlas;
use DecodeLabs\Systemic;
use DecodeLabs\Terminus\Session;

use DecodeLabs\Glitch;
use DecodeLabs\Exceptional;

class Bridge implements IBridge
{
    protected $_nodePath;

    public function __construct()
    {
        $this->_nodePath = df\Launchpad::$app->getLocalDataPath().'/node';
    }

    public function find($name)
    {
        return is_file($this->_nodePath.'/node_modules/'.$name.'/package.json');
    }

    public function npmInstall(string $name, ?Session $session=null)
    {
        Atlas::$fs->createDir($this->_nodePath);

        $result = Systemic::$process->newLauncher('npm', [
                '--loglevel=error',
                'install',
                $name
            ])
            ->setWorkingDirectory($this->_nodePath)
            //->setDecoratable(false)
            ->thenIf($session !== null, function ($launcher) use ($session) {
                $launcher->setBroker($session->getBroker());
            })
            ->launch();

        if ($result->hasError()) {
            throw Exceptional::Runtime(
                $result->getError()
            );
        }

        return $this;
    }

    public function execute($path, $data)
    {
        Atlas::$fs->createDir($this->_nodePath);
        Glitch::incomplete($path);
    }

    public function evaluate($js, $data=null)
    {
        Atlas::$fs->createDir($this->_nodePath);

        $payload = flex\Json::toString([
            'js' => $js,
            'data' => $data
        ]);

        if (!is_file($this->_nodePath.'/evaluate.js')) {
            Atlas::$fs->copyFile(__DIR__.'/evaluate.js', $this->_nodePath.'/evaluate.js');
        }

        $bin = Systemic::$os->which('node');

        $result = Systemic::$process->newLauncher($bin, [
                $this->_nodePath.'/evaluate.js'
            ])
            ->setWorkingDirectory($this->_nodePath)
            ->setDecoratable(false)
            ->setInputGenerator(function () use ($payload) {
                return $payload;
            })
            ->launch();

        $output = $result->getOutput();

        if ($result->hasError() && empty($output)) {
            $error = $result->getError();
            $e = Exceptional::Runtime($error);

            if (!preg_match('/deprecat/i', $error)) {
                throw $e;
            } else {
                Glitch::logException($e);
            }
        }

        $output = flex\Json::fromString($output);
        return $output['result'];
    }
}
