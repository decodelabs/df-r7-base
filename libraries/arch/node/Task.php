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

use DecodeLabs\Genesis;
use DecodeLabs\Terminus as Cli;
use DecodeLabs\Systemic;
use DecodeLabs\Exceptional;

abstract class Task extends Base implements ITaskNode
{
    public const SCHEDULE = null;
    public const SCHEDULE_PRIORITY = 'medium';
    public const SCHEDULE_AUTOMATIC = false;

    public const CHECK_ACCESS = false;

    public function __construct(arch\IContext $context)
    {
        parent::__construct($context);
        $this->init();
    }

    protected function init(): void
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


    public function prepareArguments(): array
    {
        return [];
    }

    public function runChild($request, bool $announce=true)
    {
        $request = $this->context->uri->directoryRequest($request);
        $context = $this->context->spawnInstance($request, true);
        $node = Base::factory($context);

        if (!$node instanceof self) {
            throw Exceptional::Definition(
                'Child node '.$request.' does not extend arch\\node\\Task'
            );
        }

        if ($announce) {
            $reqString = ltrim(substr((string)$request, strlen('directory://')), '/');
            Cli::comment($reqString);
        }

        $output = $node->dispatch();

        return $output;
    }


    public function ensureDfSource()
    {
        if (!Genesis::$build->isCompiled()) {
            return $this;
        }

        Cli::notice('Switching to source mode');
        Cli::newLine();

        $user = Systemic::$process->getCurrent()->getOwnerName();

        throw new arch\ForcedResponse(function () use ($user) {
            $args = $_SERVER['argv'];
            $path = array_shift($args);
            $args[] = '--df-source';

            Systemic::$process->newScriptLauncher($path, $args, null, $user)
                ->setSession(Cli::getSession())
                ->launch();
        });
    }


    public function launch($request)
    {
        Cli::notice('Switching to new process');
        Cli::newLine();

        $user = Systemic::$process->getCurrent()->getOwnerName();
        $this->task->launch($request, Cli::getSession(), $user, true);
    }


    // Interaction
    protected function _askFor(string $label, callable $validator, ?string $default=null, bool $confirm=false)
    {
        $output = null;

        Cli::newQuestion($label, $default)
            ->setValidator(function ($answer, $session) use ($validator, &$output) {
                $valid = $validator($answer);

                if ($valid instanceof core\validate\IField) {
                    $valid = $valid->getValidator();
                }

                if ($valid instanceof core\validate\IHandler) {
                    $key = current(array_keys($valid->getFields()));
                    $valid->validate([$key => $answer]);

                    foreach ($valid->getCurrentData()->{$key}->getErrors() as $error) {
                        $session->error($error);
                    }

                    $answer = $valid[$key];
                    $valid = $valid->isValid();
                }

                $output = $answer;
                return $valid;
            })
            ->setConfirm($confirm)
            ->prompt();

        return $output;
    }
}
