<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\application\http;

use df;
use df\core;
use df\arch;

abstract class RootActionRouter extends arch\router\Base {
    
    public function routeIn(arch\IRequest $request) {
        $basename = $request->path->getBasename();

        foreach($this->_matches as $match => $func) {
            if(substr($match, 0, 1) == '/') {
                if(preg_match($match, $basename, $matches)) {
                    return $this->{$func}($request, $matches);
                }
            } else if($match == $basename) {
                return $this->{$func}($request, [$basename]);
            }
        }
        
        return false;
    }
    
    public function routeOut(arch\IRequest $request) {
        return $request;
    }
}