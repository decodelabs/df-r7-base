<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\app\runner\http;

use df;
use df\core;
use df\arch;

abstract class RootNodeRouter extends arch\router\Base
{
    protected $_matches = [];

    public function routeIn(arch\IRequest $request)
    {
        $basename = $request->path->getBasename();

        foreach ($this->_matches as $match => $func) {
            if (substr($match, 0, 1) == '/') {
                if (preg_match($match, $basename, $matches)) {
                    return $this->{$func}($request, $matches);
                }
            } elseif ($match == $basename) {
                return $this->{$func}($request, [$basename]);
            }
        }

        return null;
    }

    public function routeOut(arch\IRequest $request)
    {
        return $request;
    }
}
