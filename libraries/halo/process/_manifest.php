<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\process;

use df;
use df\core;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
class LogicException extends \LogicException implements IException {}


// Interfaces
interface IProcess {
    public static function getCurrentProcessId();
    public function getTitle();
    public function getProcessId();

    public function isAlive();
    public function kill();
    public function sendSignal($signal);

    public function isPrivileged();
}


interface IManagedProcess extends IProcess {
    public function setTitle(string $title=null);
    public function getParentProcessId();
    public function setPriority($priority);
    public function getPriority();

    public function setIdentity($uid, $gid);

    public function setOwnerId($id);
    public function getOwnerId();

    public function setOwnerName($name);
    public function getOwnerName();

    public function setGroupId($id);
    public function getGroupId();

    public function setGroupName($name);
    public function getGroupName();

    public function hasPidFile();
    public function setPidFilePath($path);
    public function getPidFilePath();

    public function canFork();
    public function fork();
    public function delegate();
}



trait TPidFileProvider {

    protected $_pidFile;

    public function hasPidFile() {
        return $this->_pidFile !== null;
    }

    public function setPidFilePath($path) {
        $dirname = dirname($path);
        core\fs\Dir::create($dirname, 0755);

        $write = true;
        $pid = $this->getProcessId();

        if(is_file($path)) {
            $oldPid = file_get_contents($path);

            if($oldPid == $pid) {
                $write = false;
            } else if(self::isProcessIdLive($oldPid)) {
                throw new RuntimeException(
                    'PID file '.basename($path).' already exists and is live with pid of '.$oldPid
                );
            }
        }


        if($write) {
            try {
                file_put_contents($path, $pid);
            } catch(\Throwable $e) {
                throw new RuntimeException(
                    'Unable to write PID file', 0, $e
                );
            }
        }

        $this->_pidFile = $path;
        return $this;
    }

    public function getPidFilePath() {
        return $this->_pidFile;
    }
}



interface ISignal {
    public function getName(): string;
    public function getNumber();
}



interface ILauncher {
    public function setProcessName($name);
    public function getProcessName();
    public function setArgs(...$args);
    public function getArgs();
    public function setPath($path);
    public function getPath();
    public function setUser($user);
    public function getUser();
    public function isPrivileged();
    public function setTitle(string $title=null);
    public function getTitle();
    public function setPriority($priority);
    public function getPriority();
    public function setWorkingDirectory($path);
    public function getWorkingDirectory();
    public function setMultiplexer(core\io\IMultiplexer $multiplexer=null);
    public function getMultiplexer();
    public function setGenerator($generator);
    public function getGenerator();

    public function launch();
    public function launchBackground();
    public function launchManaged();
}


// Result
interface IResult {
    public function registerFailure();
    public function hasLaunched();

    public function registerCompletion();
    public function hasCompleted();

    public function getTimer();

    public function setOutput($output);
    public function appendOutput($output);
    public function hasOutput();
    public function getOutput();


    public function setError($error);
    public function appendError($error);
    public function hasError();
    public function getError();
}
