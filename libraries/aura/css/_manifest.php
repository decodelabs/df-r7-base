<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\css;

use DecodeLabs\Terminus\Session;
use df\core;

use df\link;

interface IProcessor
{
    public function getSettings(): core\collection\ITree;
    public function setup(?Session $session = null);
    public function process($cssPath, ?Session $session = null);
}


interface ISassBridge
{
    public function setCliSession(?Session $session);
    public function getCliSession(): ?Session;

    public function getHttpResponse(): link\http\IResponse;
    public function getMapHttpResponse(): link\http\IResponse;
    public function getCompiledPath(): string;
    public function compile(bool $doNotWait = false): void;
}
