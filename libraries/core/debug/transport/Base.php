<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug\transport;

use df;
use df\core;

class Base implements core\debug\ITransport {
    
    protected $_isExecuting = false;
    
    public function execute(core\debug\IContext $context) {
        if($this->_isExecuting) {
            throw new \Exception(
                'Debug transport is already executing'
            );
        }
        
        $this->_isExecuting = true;
        
        if(isset($_SERVER['HTTP_HOST'])) {
            header('Content-Type: text/plain');
        }
        
        require_once dirname(__DIR__).'/renderer/PlainText.php';
        
        $renderer = new core\debug\renderer\PlainText($context);
        echo $renderer->render();
        
        df\Launchpad::shutdown();
    }
}
