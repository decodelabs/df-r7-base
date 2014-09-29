<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flow\mail\transport;

use df;
use df\core;
use df\flow;
use df\arch;
    
class Capture extends Base {

    public static function getDescription() {
        return 'Dummy transport stored in local database for testing purposes';
    }

    public function send(flow\mail\IMessage $message) {
        $this->_prepareMessage($message);
        
        $manager = flow\Manager::getInstance();
        $model = $manager->getMailModel();

        $record = $model->captureMail($message);

        $manager->flashNow('mail.capture', 'A new email has been received at the testing mail inbox', 'debug')
            ->setDescription('Mail is stored locally when in development mode so you don\'t spam your test users')
            ->setLink('~mail/capture/details?mail='.$record['id']);

        return true;
    }
}