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

use DecodeLabs\Terminus\Cli;
use DecodeLabs\Glitch;
use DecodeLabs\Systemic;

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
            $this->io = df\Launchpad::$runner->getMultiplexer();
        }

        return parent::dispatch();
    }

    public function runChild($request, bool $announce=true)
    {
        $request = $this->context->uri->directoryRequest($request);
        $context = $this->context->spawnInstance($request, true);
        $node = Base::factory($context);

        if (!$node instanceof self) {
            throw Glitch::EDefinition(
                'Child node '.$request.' does not extend arch\\node\\Task'
            );
        }

        if ($announce) {
            $reqString = ltrim(substr((string)$request, strlen('directory://')), '/');
            Cli::comment($reqString);
        }

        $node->io = $this->io;
        $output = $node->dispatch();

        return $output;
    }


    public function ensureDfSource()
    {
        if (!df\Launchpad::$isCompiled) {
            return $this;
        }

        Cli::notice('Switching to source mode');
        Cli::newLine();

        $user = Systemic::$process->getCurrent()->getOwnerName();
        $request = clone $this->request;

        throw new arch\ForcedResponse(function () use ($user, $request) {
            $this->task->launch($request, Cli::getSession(), $user, true);
        });
    }


    // Interaction
    protected function _askFor($label, callable $validator, $default=null, $check=false)
    {
        do {
            Cli::{'cyan'}($label.': ');

            if ($default !== null) {
                Cli::write('['.$default.'] ');
            }

            $answer = trim(Cli::readLine());

            if (!strlen($answer) && $default !== null) {
                $answer = $default;
            }

            $valid = $validator($answer);

            if ($valid instanceof core\validate\IField) {
                $valid = $valid->getValidator();
            }

            if ($valid instanceof core\validate\IHandler) {
                $key = current(array_keys($valid->getFields()));
                $valid->validate([$key => $answer]);

                foreach ($valid->getCurrentData()->{$key}->getErrors() as $error) {
                    Cli::error($error);
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

                $valid = Cli::confirm('Is this correct? '.$answerString, true)->prompt();
            }
        } while (!$valid);

        return $answer;
    }

    protected function _askPassword($label, $repeat=false, $required=true, $hash=false)
    {
        do {
            Cli::{'cyan'}($label.': ');
            $snapshot = Cli::snapshotStty();
            Cli::toggleInputEcho(false);

            $answer = trim(Cli::readLine());
            Cli::restoreStty($snapshot);
            Cli::newLine();

            $validator = $this->data->newValidator()
                ->addField('password')
                    ->isRequired((bool)$required);

            $data = ['password' => $answer];

            if ($repeat) {
                Cli::{'cyan'}('Repeat '.lcfirst($label).': ');
                $snapshot = Cli::snapshotStty();
                Cli::toggleInputEcho(false);

                $repeatAnswer = trim(Cli::readLine());
                Cli::restoreStty($snapshot);
                Cli::newLine();

                $validator->setMatchField('repeat');
                $data['repeat'] = $repeatAnswer;
            }

            $validator = $validator->validate($data);

            $valid = $validator->isValid();
            $answer = $hash ?
                $validator['password'] :
                $validator->data['password'];

            foreach ($validator->data->password->getErrors() as $error) {
                Cli::error($error);
            }
            foreach ($validator->data->repeat->getErrors() as $error) {
                Cli::error($error);
            }
        } while (!$valid);

        return $answer;
    }
}
