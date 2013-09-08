<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\router;

use df;
use df\core;
use df\arch;
    
abstract class Base implements arch\IRouter {

    public function newRequest($input, core\collection\ITree $query=null, array $filter=null) {
        $output = arch\Request::factory($input);

        if($query) {
            $this->importQuery($output, $query, $filter);
        }

        return $output;
    }

    public function importQuery(arch\IRequest $request, core\collection\ITree $query, array $filter=null) {
        $newQuery = $request->query;

        foreach($query as $key => $node) {
            if($filter && in_array($key, $filter)) {
                continue;
            }

            $newQuery->{$key} = clone $node;
        }

        return $request;
    }
}