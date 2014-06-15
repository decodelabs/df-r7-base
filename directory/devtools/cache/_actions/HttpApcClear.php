<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\devtools\cache\_actions;

use df;
use df\core;
use df\apex;
use df\arch;

class HttpApcClear extends arch\Action {
    
    use apex\directory\devtools\cache\_actions\TApcClear;

    const DEFAULT_ACCESS = arch\IAccess::ALL;
    const CHECK_ACCESS = false;

    public function executeAsJson() {
        if($_SERVER['REMOTE_ADDR'] == $_SERVER['SERVER_ADDR']) {
            $cleared = $this->_clearApc();
        } else {
            //$this->throwError(403, 'This action can only be triggered from localhost');
            $cleared = 0;
        }

        return $this->data->jsonEncode([
            'cleared' => $cleared,
            'addr' => $_SERVER['REMOTE_ADDR']
        ]);
    }
}