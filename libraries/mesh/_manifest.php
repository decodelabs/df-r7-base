<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\mesh;

use df;
use df\core;
use df\mesh;

interface IManager extends mesh\event\IEmitter, core\IManager
{
    // Jobs
    public function newJobQueue();

    // Handlers
    public function registerHandler($scheme, IHandler $handler);
    public function unregisterHandler($scheme);
    public function getHandler($scheme);
    public function getHandlers();

    // Entities
    public function fetchEntity($locator);
}



interface IHandler
{
}

interface IEntityHandler extends IHandler
{
    public function fetchEntity(IManager $manager, array $node);
}

interface IEventHandler extends IHandler, mesh\event\IEmitter
{
}
