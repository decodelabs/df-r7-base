<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug;

use df;
use df\core;
use df\plug;
use df\flow;
use df\arch;
use df\aura;

use DecodeLabs\Dictum;

class Comms implements core\ISharedHelper
{
    use core\TSharedHelper;

    protected $_manager;

    public function __construct(core\IContext $context)
    {
        $this->context = $context;
        $this->_manager = flow\Manager::getInstance();
    }

    public function getManager()
    {
        return $this->_manager;
    }


    // Flash messages
    public function flash($id, $message=null, $type=null)
    {
        return $this->_manager->flash($id, $message, $type);
    }

    public function flashNow($id, $message=null, $type=null)
    {
        return $this->_manager->flashNow($id, $message, $type);
    }

    public function flashAlways($id, $message=null, $type=null)
    {
        return $this->_manager->flashAlways($id, $message, $type);
    }

    public function removeConstantFlash($id)
    {
        $this->_manager->removeConstantFlash($id);
        return $this;
    }

    public function removeQueuedFlash($id)
    {
        $this->_manager->removeQueuedFlash($id);
        return $this;
    }


    // Flash shortcuts
    public function flashInfo($id, $message=null)
    {
        return $this->flash($id, $message, 'info');
    }

    public function flashSuccess($id, $message=null)
    {
        return $this->flash($id, $message, 'success');
    }

    public function flashWarning($id, $message=null)
    {
        return $this->flash($id, $message, 'warning');
    }

    public function flashError($id, $message=null)
    {
        return $this->flash($id, $message, 'error');
    }

    public function flashDebug($id, $message=null)
    {
        return $this->flash($id, $message, 'debug');
    }

    public function flashSaveSuccess($itemName, $message=null)
    {
        return $this->flash(
            Dictum::id($itemName).'.save',
            $message ?? $this->context->_('The %i% was successfully saved', ['%i%' => $itemName]),
            'success'
        );
    }



    // Mail
    public function newMail($subject, $body, ...$to): flow\mail\IMessage
    {
        return new flow\mail\Message($subject, $body, ...$to);
    }

    public function newTextMail($subject, $body, ...$to): flow\mail\IMessage
    {
        return $this->newMail($subject, null, ...$to)
            ->setBodyText($body);
    }

    public function newAdminMail($subject, $body, $forceSend=false)
    {
        return $this->newMail($subject, $body, true)
            ->shouldForceSend($forceSend);
    }

    public function newAdminTextMail($subject, $body, $forceSend=false)
    {
        return $this->newTextMail($subject, $body, true)
            ->shouldForceSend($forceSend);
    }

    public function sendAdminMail($subject, $body, $forceSend=false)
    {
        return $this->newAdminMail($subject, $body, $forceSend)->send();
    }

    public function sendAdminTextMail($subject, $body, $forceSend=false)
    {
        return $this->newAdminTextMail($subject, $body, $forceSend)->send();
    }



    public function prepareMail($path, array $slots=null, $forceSend=false)
    {
        if (!($context = $this->context) instanceof arch\IContext) {
            $context = arch\Context::factory();
        }

        $output = arch\mail\Base::factory($context, $path)
            ->shouldForceSend($forceSend);

        if ($slots) {
            $output->setSlots($slots);
        }

        return $output;
    }

    public function prepareAdminMail($path, array $slots=null, $forceSend=false)
    {
        return $this->prepareMail($path, $slots, $forceSend)->shouldSendToAdmin(true);
    }

    public function preparePreviewMail($path)
    {
        $output = $this->prepareMail($path);
        $output->preparePreview();
        return $output;
    }

    public function sendPreparedMail($path, array $slots=null, $forceSend=false)
    {
        return $this->prepareMail($path, $slots, $forceSend)->send();
    }

    public function sendPreparedAdminMail($path, array $slots=null, $forceSend=false)
    {
        return $this->prepareAdminMail($path, $slots, $forceSend)->send();
    }
}
