<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\mail\transport;

use df;
use df\core;
use df\axis;
use df\arch;
    
class DevMail extends Base {

    public static function getDescription() {
        return 'Dummy transport stored in local database for testing purposes';
    }

    public function send(core\mail\IMessage $message) {
        $this->_prepareMessage($message);
        $model = axis\Model::factory('mail');

        if(!$model instanceof core\mail\IMailModel) {
            throw new core\mail\RuntimeException(
                'Mail model does not implements core\\mail\\IMailModel'
            );
        }

        $record = $model->storeDevMail($message);

        $flash = arch\flash\Manager::getInstance();
        $flash->setInstantMessage(
            $flash->newMessage('devMail.send', 'A new email has been received at the dev mail inbox', 'debug')
                ->setDescription('Mail is stored locally when in development mode so you don\'t spam your test users')
                ->setLink('~devtools/mail/dev/details?mail='.$record['id'])
        );

        return true;
    }
}