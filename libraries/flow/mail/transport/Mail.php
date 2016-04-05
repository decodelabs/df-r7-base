<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flow\mail\transport;

use df;
use df\core;
use df\flow;

class Mail extends Base {

    public static function getDescription() {
        return 'PHP native mail()';
    }

    public function sendLegacy(flow\mail\ILegacyMessage $message) {
        $this->_prepareMessage($message);
        $headers = $message->getHeaderString(['to', 'subject']);
        $to = $message->getHeaders()->get('to');
        $body = $message->getBodyString();
        $additional = null;

        if($returnPath = $message->getReturnPath()) {
            $additional = '-f'.$returnPath->getAddress();
        }

        return mail($to, $message->getSubject(), $body, $headers, $additional);
    }
}