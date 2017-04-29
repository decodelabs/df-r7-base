<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\css;

use df;
use df\core;
use df\aura;
use df\link;


interface IProcessor {
    public function process($cssPath);
}


interface ISassBridge {
    public function getHttpResponse(): link\http\IResponse;
    public function getMapHttpResponse(): link\http\IResponse;
    public function getCompiledPath(): string;
    public function compile(): void;
}
