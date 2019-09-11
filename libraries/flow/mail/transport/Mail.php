<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flow\mail\transport;

use df;
use df\core;
use df\flow;

class Mail extends Base
{
    public static function getDescription()
    {
        return 'PHP native mail()';
    }

    public function send(flow\mail\IMessage $message, flow\mime\IMultiPart $mime)
    {
        $headers = $mime->getHeaders();
        $headerString = $mime->getHeaderString(['to', 'subject']);
        $to = $headers->get('to');
        $body = $mime->getBodyString();
        $additional = null;

        if ($returnPath = $headers->get('return-path')) {
            $additional = '-f'.$returnPath;
        } elseif ($from = $message->getFromAddress()) {
            $additional = '-f'.$from->getAddress();
        }

        return mail($to, $headers->get('subject'), $body, $headerString, $additional);
    }
}
