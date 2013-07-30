<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flow\mail\transport;

use df;
use df\core;
use df\flow;
use df\axis;
use df\arch;
    
class DevMail extends Base {

    public static function getDescription() {
        return 'Dummy transport stored in local database for testing purposes';
    }

    public function send(flow\mail\IMessage $message) {
        $this->_prepareMessage($message);
        $model = axis\Model::factory('mail');

        if(!$model instanceof flow\mail\IMailModel) {
            throw new flow\mail\RuntimeException(
                'Mail model does not implements flow\\mail\\IMailModel'
            );
        }

        $record = $model->storeDevMail($message);

        flow\Manager::getInstance()->flashNow('devMail.send', 'A new email has been received at the dev mail inbox', 'debug')
            ->setDescription('Mail is stored locally when in development mode so you don\'t spam your test users')
            ->setLink('~devtools/mail/dev/details?mail='.$record['id']);

        return true;
    }
}