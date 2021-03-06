<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\cache\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;

use DecodeLabs\Exceptional;

class HttpApcuClear extends arch\node\Base
{
    use TApcuClear;

    const DEFAULT_ACCESS = arch\IAccess::ALL;
    const OPTIMIZE = true;

    public function executeAsJson()
    {
        //if($_SERVER['REMOTE_ADDR'] == $_SERVER['SERVER_ADDR']) {
        $cleared = $this->_clearApcu();
        //} else {
        //throw Exceptional::Runtime(
        //'This action can only be triggered from localhost'
        //);
        //$cleared = null;
        //}

        return $this->data->toJson([
            'cleared' => $cleared,
            'addr' => $_SERVER['REMOTE_ADDR'].' => '.$_SERVER['SERVER_ADDR']
        ]);
    }
}
