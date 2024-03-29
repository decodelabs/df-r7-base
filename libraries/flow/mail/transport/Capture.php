<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\flow\mail\transport;

use DecodeLabs\Genesis;

use df\flow;

class Capture extends Base
{
    public static function getDescription()
    {
        return 'Dummy transport stored in local database for testing purposes';
    }

    public function send(flow\mail\IMessage $message, flow\mime\IMultiPart $mime)
    {
        $manager = flow\Manager::getInstance();
        $model = $manager->getMailModel();

        $record = $model->captureMail($mime);

        if (!Genesis::$environment->isProduction()) {
            $manager->flashNow('mail.capture', 'A new email has been received at the testing mail inbox', 'debug')
                ->setDescription('Mail is stored locally when in development mode so you don\'t spam your test users')
                ->setLink('~mail/capture/details?mail=' . $record['id']);
        }

        return $this;
    }
}
