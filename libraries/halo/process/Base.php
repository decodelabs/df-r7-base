<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\process;

use df;
use df\core;
use df\halo;

abstract class Base implements IProcess
{
    private static $_current;

    protected $_processId;
    protected $_title;

    public static function getCurrent()
    {
        if (!self::$_current) {
            $class = self::_getSystemClass();
            $pid = $class::getCurrentProcessId();
            self::$_current = new $class($pid, 'Current process');
        }

        return self::$_current;
    }

    public static function fromPid($pid)
    {
        $class = self::_getSystemClass();
        return new $class($pid, 'PID: '.$pid);
    }

    protected static function _getSystemClass()
    {
        $class = 'df\\halo\\process\\'.Systemic::$os->getName().'Managed';

        if (!class_exists($class)) {
            $class = 'df\\halo\\process\\'.Systemic::$os->getPlatformType().'Managed';

            if (!class_exists($class)) {
                throw new halo\process\RuntimeException(
                    'Sorry, managed processes aren\'t currently supported on this platform!'
                );
            }
        }

        return $class;
    }


    public static function launch($process, $args=null, $path=null, core\io\IMultiplexer $multiplexer=null, $user=null)
    {
        return self::newLauncher($process, $args, $path)
            ->setMultiplexer($multiplexer)
            ->setUser($user)
            ->launch();
    }

    public static function launchScript($path, $args=null, core\io\IMultiplexer $multiplexer=null, $user=null)
    {
        return self::newScriptLauncher($path, $args)
            ->setMultiplexer($multiplexer)
            ->setUser($user)
            ->launch();
    }

    public static function launchBackground($process, $args=null, $path=null, core\io\IMultiplexer $multiplexer=null, $user=null)
    {
        return self::newLauncher($process, $args, $path)
            ->setMultiplexer($multiplexer)
            ->setUser($user)
            ->launchBackground();
    }

    public static function launchBackgroundScript($path, $args=null, $user=null)
    {
        return self::newScriptLauncher($path, $args)
            ->setUser($user)
            ->launchBackground();
    }

    public static function launchManaged($process, $args=null, $path=null, $user=null)
    {
        return self::newLauncher($process, $args, $path)
            ->setUser($user)
            ->launchManaged();
    }

    public static function launchManagedScript($path, $args=null, $user=null)
    {
        return self::newScriptLauncher($path, $args)
            ->setUser($user)
            ->launchManaged();
    }

    public static function newLauncher($process, $args=null, $path=null)
    {
        return halo\process\launcher\Base::factory($process, $args, $path);
    }

    public static function newScriptLauncher($path, $args=null)
    {
        $envConfig = core\environment\Config::getInstance();
        $binaryPath = $envConfig->getBinaryPath('php');

        if ($binaryPath === 'php') {
            $binaryPath = Systemic::$os->which('php');
        }

        if (!file_exists($binaryPath)) {
            $binaryPath = 'php';
        }

        $phpName = basename($binaryPath);
        $phpPath = null;

        if ($phpName != $binaryPath) {
            $phpPath = dirname($binaryPath);
        }

        return halo\process\launcher\Base::factory($phpName, [trim($path), $args], $phpPath);
    }



    public function __construct($processId, ?string $title)
    {
        $this->_processId = $processId;
        $this->_title = $title;
    }

    public function getProcessId()
    {
        return $this->_processId;
    }

    public function getTitle(): ?string
    {
        return $this->_title;
    }
}
