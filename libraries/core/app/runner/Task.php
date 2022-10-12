<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\app\runner;

use df\core;
use df\arch\Context;

use DecodeLabs\Exceptional;

class Task extends Base implements core\IContextAware
{
    protected $_context;


    // Context
    public function setContext(Context $context)
    {
        $this->_context = $context;
        return $this;
    }

    public function getContext()
    {
        if (!$this->_context) {
            throw Exceptional::NoContext(
                'A context is not available until the application has been dispatched'
            );
        }

        return $this->_context;
    }

    public function hasContext()
    {
        return $this->_context !== null;
    }



    // Execute
    public function dispatch(): void
    {
    }
}
