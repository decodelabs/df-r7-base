<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\view;

use df;
use df\core;
use df\aura;
use df\arch;
use df\flow;
use df\flex;

class Mail extends Base {

    protected $_message;

    public function __construct($type, arch\IContext $context) {
        parent::__construct($type, $context);
        $this->_message = new flow\mail\Message();
    }

    public function __call($method, array $args) {
        if(method_exists($this->_message, $method)) {
            $output = call_user_func_array([$this->_message, $method], $args);

            if($output === $this->_message) {
                return $this;
            }

            return $output;
        }

        throw new BadMethodCallException(
            'Method '.$method.' does not exist'
        );
    }

    public function send(flow\mail\ITransport $transport=null) {
        $html = $this->render();
        $this->_message->setBodyHtml($html);

        if($this->_message->getBodyText() === null) {
            $handler = new flex\html\Parser($html);
            $this->_message->setBodyText($handler->toText());
        }

        return $this->_message->send($transport);
    }
}