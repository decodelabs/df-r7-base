<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\mail;

use df;
use df\core;
use df\apex;
use df\axis;
use df\flow;
    
class Model extends axis\Model implements flow\mail\IMailModel {

    public function storeDevMail(flow\mail\IMessage $message) {
        return $this->devMail->store($message);
    }
}