<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\node;

use df;
use df\core;
use df\arch;
use df\halo;

abstract class Task extends Base implements ITaskNode {

    const SCHEDULE = null;
    const SCHEDULE_ENVIRONMENT_MODE = null;
    const SCHEDULE_PRIORITY = 'medium';
    const SCHEDULE_AUTOMATIC = false;

    const CHECK_ACCESS = false;

    public $io;

    public function __construct(arch\IContext $context) {
        parent::__construct($context);
        $this->init();
    }

    protected function init() {}


// Schedule
    public static function getSchedule() {
        $schedule = static::SCHEDULE;

        if(empty($schedule)) {
            $schedule = null;
        }

        return $schedule;
    }

    public static function getScheduleEnvironmentMode() {
        return static::SCHEDULE_ENVIRONMENT_MODE;
    }

    public static function getSchedulePriority() {
        return core\unit\Priority::factory(static::SCHEDULE_PRIORITY);
    }

    public static function shouldScheduleAutomatically() {
        return (bool)static::SCHEDULE_AUTOMATIC;
    }


    public function extractCliArguments(core\cli\ICommand $command) {
        // Do nothing
    }


// Dispatch
    public function dispatch() {
        if(!$this->io) {
            $this->io = $this->task->getSharedIo();
        }

        return parent::dispatch();
    }

    public function runChild($request, $incLevel=true) {
        $request = $this->context->uri->directoryRequest($request);
        $context = $this->context->spawnInstance($request, true);
        $node = Base::factory($context);

        if(!$node instanceof self) {
            $this->throwError(500, 'Child node '.$request.' does not extend arch\\node\\Task');
        }

        $node->io = $this->io;

        if($incLevel) {
            $this->io->incrementLineLevel();
        }

        $output = $node->dispatch();

        if($incLevel) {
            $this->io->decrementLineLevel();
        }

        return $output;
    }

    public function runChildQuietly($request) {
        $request = $this->context->uri->directoryRequest($request);
        $context = $this->context->spawnInstance($request, true);
        $node = Base::factory($context);

        if(!$node instanceof self) {
            $this->throwError(500, 'Child node '.$request.' does not extend arch\\node\\Task');
        }

        $capture = $this->task->shouldCaptureBackgroundTasks();
        $this->task->shouldCaptureBackgroundTasks(false);

        $node->io = new core\io\Multiplexer([
            $output = new core\fs\MemoryFile()
        ]);

        $node->dispatch();
        $this->task->shouldCaptureBackgroundTasks($capture);

        return $output;
    }



// Environment
    public function ensureEnvironmentMode($mode) {
        $mode = core\EnvironmentMode::factory($mode)->getOption();
        $current = $this->application->getEnvironmentMode();

        if($mode == $current) {
            return $this;
        }

        if(!$this->application->hasEnvironmentMode($mode)) {
            $this->io->writeErrorLine('!! Unable to switch to '.$mode.' mode, entry is unavailable');
            throw new arch\ForcedResponse(null);
        }

        throw new arch\ForcedResponse(function() use($mode) {
            $this->task->shouldCaptureBackgroundTasks(true);
            $this->task->launch($this->request, $this->io, $mode);
        });
    }

    public function promptEnvironmentMode($mode, $default=false) {
        $mode = core\EnvironmentMode::factory($mode)->getOption();
        $current = $this->application->getEnvironmentMode();

        if($mode == $current) {
            return $this;
        }

        if(!$this->_askBoolean('Currently in '.$current.' mode - switch to '.$mode.'?', $default)) {
            return $this;
        }

        return $this->ensureEnvironmentMode($mode);
    }



// Interaction
    protected function _askFor($label, Callable $validator, $default=null, $check=false) {
        do {
            $this->io->write('>> '.$label.': ');

            if($default !== null) {
                $this->io->write('['.$default.'] ');
            }

            $answer = trim($this->io->readLine());

            if(!strlen($answer) && $default !== null) {
                $answer = $default;
            }

            $valid = $validator($answer);

            if($valid instanceof core\validate\IField) {
                $valid = $valid->validator;
            }

            if($valid instanceof core\validate\IHandler) {
                $key = current(array_keys($valid->getFields()));
                $valid->validate([$key => $answer]);

                foreach($valid->data->{$key}->getErrors() as $error) {
                    $this->io->writeLine('!! '.$error);
                }

                $valid = $valid->isValid();
            }

            if($valid && $check) {
                $this->io->write('>> Is this correct? '.$answer.' [Y/n] ');
                $checkAnswer = trim($this->io->readLine());
                $valid = $this->format->stringToBoolean($checkAnswer, true);
            }
        } while(!$valid);

        return $answer;
    }

    protected function _askPassword($label, $repeat=false, $required=true, $hash=false) {
        do {
            $this->io->write('>> '.$label.': ');
            system('stty -echo');
            $answer = trim($this->io->readLine());
            system('stty echo');
            $this->io->writeLine();

            $validator = $this->data->newValidator()
                ->addField('password')
                    ->isRequired((bool)$required);

            $data = ['password' => $answer];

            if($repeat) {
                $this->io->write('>> Repeat '.lcfirst($label).': ');
                system('stty -echo');
                $repeatAnswer = trim($this->io->readLine());
                system('stty echo');
                $this->io->writeLine();
                $validator->setMatchField('repeat');
                $data['repeat'] = $repeatAnswer;
            }

            $validator = $validator->validate($data);

            $valid = $validator->isValid();
            $answer = $hash ?
                $validator['password'] :
                $validator->data['password'];

            foreach($validator->data->password->getErrors() as $error) {
                $this->io->writeLine('!! '.$error);
            }
            foreach($validator->data->repeat->getErrors() as $error) {
                $this->io->writeLine('!! '.$error);
            }
        } while(!$valid);

        return $answer;
    }

    protected function _askBoolean($label, $default=false) {
        $default = (bool)$default;
        $this->io->write('>> '.$label.' '.($default ? '[Y/n]' : '[y/N]').' ');
        $answer = trim($this->io->readLine());
        return $this->format->stringToBoolean($answer, $default);
    }
}