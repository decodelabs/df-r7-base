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
            $output->importQuery($query, $filter);
        }

        return $output;
    }
}