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
        if($_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
            $this->throwError(401, 'This action can only be triggered from localhost');
        }

        return $this->data->jsonEncode(['cleared' => $this->_clearApc()]);
    }
}