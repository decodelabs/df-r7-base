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

abstract class Task extends Base implements ITaskNode
{
    const SCHEDULE = null;
    const SCHEDULE_PRIORITY = 'medium';
    const SCHEDULE_AUTOMATIC = false;

    const CHECK_ACCESS = false;

    public $io;

    public function __construct(arch\IContext $context)
    {
        parent::__construct($context);
        $this->init();
    }

    protected function init()
    {
    }


    // Schedule
    public static function getSchedule(): ?string
    {
        $schedule = static::SCHEDULE;

        if (empty($schedule)) {
            $schedule = null;
        }

        return $schedule;
    }

    public static function getSchedulePriority(): string
    {
        return core\unit\Priority::normalize(static::SCHEDULE_PRIORITY);
    }

    public static function shouldScheduleAutomatically(): bool
    {
        return (bool)static::SCHEDULE_AUTOMATIC;
    }


    public function extractCliArguments(core\cli\ICommand $command)
    {
        // Do nothing
    }


    // Dispatch
    public function dispatch()
    {
        if (!$this->io) {
            $this->io = $this->task->getSharedIo();
        }

        return parent::dispatch();
    }

    public function runChild($request, bool $incLevel=true)
    {
        $request = $this->context->uri->directoryRequest($request);
        $context = $this->context->spawnInstance($request, true);
        $node = Base::factory($context);

        if (!$node instanceof self) {
            throw core\Error::{'EDefinition'}(
                'Child node '.$request.' does not extend arch\\node\\Task'
            );
        }

        $node->io = $this->io;

        if ($incLevel) {
            $this->io->indent();
        }

        $output = $node->dispatch();

        if ($incLevel) {
            $this->io->outdent();
        }

        return $output;
    }

    public function runChildQuietly($request)
    {
        $request = $this->context->uri->directoryRequest($request);
        $context = $this->context->spawnInstance($request, true);
        $node = Base::factory($context);

        if (!$node instanceof self) {
            throw core\Error::{'EDefinition'}(
                'Child node '.$request.' does not extend arch\\node\\Task'
            );
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



    public function ensureDfSource()
    {
        if (!df\Launchpad::$isCompiled) {
            return $this;
        }

        $this->io->writeLine('Switching to source mode...');
        $this->io->writeLine();

        $user = Systemic::$process->getCurrent()->getOwnerName();
        $request = clone $this->request;

        throw new arch\ForcedResponse(function () use ($user, $request) {
            $this->task->shouldCaptureBackgroundTasks(true);
            $this->task->launch($request, $this->io, $user, true);
        });
    }


    // Interaction
    protected function _askFor($label, callable $validator, $default=null, $check=false)
    {
        do {
            $this->io->write('>> '.$label.': ');

            if ($default !== null) {
                $this->io->write('['.$default.'] ');
            }

            $answer = trim($this->io->readLine());

            if (!strlen($answer) && $default !== null) {
                $answer = $default;
            }

            $valid = $validator($answer);

            if ($valid instanceof core\validate\IField) {
                $valid = $valid->validator;
            }

            if ($valid instanceof core\validate\IHandler) {
                $key = current(array_keys($valid->getFields()));
                $valid->validate([$key => $answer]);

                foreach ($valid->data->{$key}->getErrors() as $error) {
                    $this->io->writeLine('!! '.$error);
                }

                $answer = $valid[$key];
                $valid = $valid->isValid();
            }

            if ($valid && $check) {
                $answerString = $answer;

                if ($answerString instanceof core\time\IDate) {
                    $answerString = $this->format->date($answerString);
                } else {
                    $answerString = (string)$answerString;
                }

                $this->io->write('>> Is this correct? '.$answerString.' [Y/n] ');
                $checkAnswer = trim($this->io->readLine());
                $valid = $this->format->stringToBoolean($checkAnswer, true);
            }
        } while (!$valid);

        return $answer;
    }

    protected function _askPassword($label, $repeat=false, $required=true, $hash=false)
    {
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

            if ($repeat) {
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

            foreach ($validator->data->password->getErrors() as $error) {
                $this->io->writeLine('!! '.$error);
            }
            foreach ($validator->data->repeat->getErrors() as $error) {
                $this->io->writeLine('!! '.$error);
            }
        } while (!$valid);

        return $answer;
    }

    protected function _askBoolean($label, $default=false)
    {
        $default = (bool)$default;
        $this->io->write('>> '.$label.' '.($default ? '[Y/n]' : '[y/N]').' ');
        $answer = trim($this->io->readLine());
        return $this->format->stringToBoolean($answer, $default);
    }
}
