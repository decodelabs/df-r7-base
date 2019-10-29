<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\daemon;

use df;
use df\core;
use df\halo;

use DecodeLabs\Terminus\Session;

interface IDaemon extends core\IContextAware
{
    public function getName(): string;
    public function getPidFilePath();
    public function setUser($user);
    public function getUser();
    public function setGroup($group);
    public function getGroup();

    public function isTesting(): bool;
    public function run();
    public function isRunning();
    public function stop();
    public function isStopped();
    public function restart();

    public function pause();
    public function isPaused();
    public function resume();
}


interface IRemote
{
    public function getName(): string;
    public function setCliSession(?Session $session);
    public function getCliSession(): ?Session;
    public function isRunning();
    public function getStatusData();
    public function getProcess();
    public function refresh();
    public function start();
    public function stop();
    public function restart();
    public function pause();
    public function resume();
    public function nudge();
    public function sendCommand($command);
}


interface IManager extends core\IManager
{
    public function isEnabled();
    public function ensureActivity();

    public function launch($name);
    public function nudge($name);
    public function getRemote($name);
    public function isRunning($name);
}
