<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\migrate;

use df\link;

interface IHandler
{
    public function getKey();
    public function getUrl();
    public function getRouter();

    public function setAsyncBatchLimit($limit);
    public function getAsyncBatchLimit();

    public function createRequest($method, $request, array $data = null);
    public function call(link\http\IRequest $request);
    public function callAsync(link\http\IRequest $request, callable $callback, ?callable $progress = null);
    public function sync();
}
