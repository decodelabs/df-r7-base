<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\mail;

use df\axis;
use df\flow;

class Model extends axis\Model implements flow\mail\IMailModel
{
    public function captureMail(flow\mime\IMultiPart $message)
    {
        return $this->capture->store($message);
    }

    public function journalMail(flow\mail\IJournalableMessage $message)
    {
        return $this->journal->store($message);
    }
}
