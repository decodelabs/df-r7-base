<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\menu\source;

use df;
use df\core;
use df\arch;
    
class Directory extends Base {

    public function loadMenu(core\uri\Url $id) {
    	core\dump($id);
    }

    public function loadAllMenus(array $whiteList=null) {
    	core\stub($whiteList);
    }
}