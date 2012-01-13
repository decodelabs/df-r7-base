<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug\transport;

use df;
use df\core;
use df\halo;

class HttpCapture extends Http {
    
    public function execute(core\debug\IContext $context) {
        require_once dirname(__DIR__).'/renderer/Html.php';
        
        $renderer = new core\debug\renderer\Html($context);
        $response = new halo\protocol\http\response\String($renderer->render(), 'text/html');
        
        $response->getHeaders()
            ->setStatusCode(501)
            ->setCacheAccess('no-cache')
            ->shouldRevalidateCache(true)
            ->canStoreCache(false);
        
        
        throw new halo\protocol\http\DebugPayload($response);
    }
}
