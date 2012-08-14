<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\mail\transport;

use df;
use df\core;
    
class Mail extends Base {

    public function send(core\mail\IMessage $message) {
    	$this->_prepareMessage($message);
    	$headers = $message->getHeaderString(['to']);
    	$to = $message->getHeaders()->get('to');
    	$body = $message->getBodyString();

    	return mail($to, $message->getSubject(), $body, $headers);
    }
}