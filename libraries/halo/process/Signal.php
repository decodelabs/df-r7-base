<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\process;

use df;
use df\core;
use df\halo;
    
class Signal implements ISignal {

    protected static $_signalMap = [
        'SIGHUP' => null,
        'SIGINT' => null,
        'SIGQUIT' => null,
        'SIGKILL' => null,
        'SIGILL' => null,
        'SIGTRAP' => null,
        'SIGABRT' => null,
        'SIGIOT' => null,
        'SIGBUS' => null,
        'SIGFPE' => null,
        'SIGUSR1' => null,
        'SIGSEGV' => null,
        'SIGUSR2' => null,
        'SIGALRM' => null,
        'SIGTERM' => null,
        'SIGSTKFLT' => null,
        'SIGCLD' => null,
        'SIGCHLD' => null,
        'SIGCONT' => null,
        'SIGTSTP' => null,
        'SIGTTIN' => null,
        'SIGTTOU' => null,
        'SIGURG' => null,
        'SIGXCPU' => null,
        'SIGXFSZ' => null,
        'SIGVTALRM' => null,
        'SIGPROF' => null,
        'SIGWINCH' => null,
        'SIGPOLL' => null,
        'SIGIO' => null,
        'SIGPWR' => null,
        'SIGSYS' => null,
        'SIGBABY' => null
    ];

    protected static $_isInit = false;

    protected $_name;
    protected $_number;

    public static function factory($signal) {
        if($signal instanceof ISignal) {
            return $signal;
        }

        $signal = self::normalizeSignalName($signal);

        if(!$signal) {
            throw new InvalidArgumentException(
                'Signal is not defined'
            );
        }

        return new self($signal);
    }


    public static function normalizeSignalName($signal) {
        if(!self::$_isInit) {
            self::$_isInit = true;

            if(extension_loaded('pcntl')) {
                foreach(self::$_signalMap as $signalName => $number) {
                    if(defined($signalName)) {
                        self::$_signalMap[$signalName] = constant($signalName);
                    }
                }
            } else {
                $list = explode(' ', trim(shell_exec("kill -l")));

                foreach($list as $i => $name) {
                    $name = 'SIG'.$name;

                    if(array_key_exists($name, self::$_signalMap)) {
                        self::$_signalMap[$name] = $i + 1;
                    }
                }
            }
        }

        if(is_string($signal)) {
            $signal = strtoupper($signal);

            if(!array_key_exists($signal, self::$_signalMap)) {
                throw new InvalidArgumentException(
                    $signal.' is not a valid signal identifier'
                );
            }
        } else if(is_numeric($signal)) {
            if(false !== ($t = array_search($signal, self::$_signalMap))) {
                $signal = $t;
            } else {
                throw new InvalidArgumentException(
                    $signal.' is not a valid signal identifier'
                );
            }
        } else {
            throw new InvalidArgumentException(
                $signal.' is not a valid signal identifier'
            );
        }

        return $signal;
    }

    protected function __construct($name) {
        $this->_name = $name;
        $this->_number = self::$_signalMap[$name];
    }

    public function getName() {
        return $this->_name;
    }

    public function getNumber() {
        return $this->_number;
    }
}