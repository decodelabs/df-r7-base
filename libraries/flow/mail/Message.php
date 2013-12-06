<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flow\mail;

use df;
use df\core;
use df\flow;
    
class Message extends flow\mime\MultiPart implements IMessage {

    protected $_from;
    protected $_to = array();
    protected $_cc = array();
    protected $_bcc = array();

    protected $_altPart;
    protected $_bodyText;
    protected $_bodyHtml;
    protected $_isPrivate = false;

    public static function fromString($string) {
        $output = parent::fromString($string);
        $headers = $output->getHeaders();

        if($headers->has('from')) {
            $output->_from = Address::factory($headers->get('from'));
        }

        if($headers->has('to')) {
            $parts = explode(',', $headers->get('to'));

            foreach($parts as $part) {
                $address = Address::factory(trim($part));
                $output->_to[$address->getAddress()] = $address;
            }
        }

        if($headers->has('cc')) {
            $parts = explode(',', $headers->get('cc'));

            foreach($parts as $part) {
                $address = Address::factory(trim($part));
                $output->_cc[$address->getAddress()] = $address;
            }
        }

        if($headers->has('bcc')) {
            $parts = explode(',', $headers->get('bcc'));

            foreach($parts as $part) {
                $address = Address::factory(trim($part));
                $output->_bcc[$address->getAddress()] = $address;
            }
        }


        if(isset($output->_parts[0]) && $output->_parts[0]->getContentType() == 'multipart/alternative') {
            $part = $output->_parts[0];

            foreach($part->getParts() as $subPart) {
                $type = $subPart->getContentType();

                if(!$output->_bodyText && $type == 'text/plain') {
                    $output->_bodyText = $subPart;

                    if($output->_bodyHtml) {
                        break;
                    }
                } else if(!$output->_bodyHtml && $type == 'text/html') {
                    $output->_bodyHtml = $subPart;

                    if($output->_bodyText) {
                        break;
                    }
                }
            }
        }
        
        return $output;
    }

// Subject
    public function setSubject($subject) {
        $this->_headers->set('subject', (string)$subject);
        return $this;
    }

    public function getSubject() {
        return $this->_headers->get('subject');
    }

// Body HTML
    public function setBodyHtml($content) {
        $this->_createAltPart();

        if($this->_bodyHtml === null) {
            $this->_bodyHtml = (new flow\mime\ContentPart($content))
                ->setContentType('text/html')
                ->setEncoding(core\string\IEncoding::QP);

            $this->_altPart->addPart($this->_bodyHtml);
        } else {
            $this->_bodyHtml->setContent($content);
        }

        return $this;
    }

    public function getBodyHtml() {
        return $this->_bodyHtml;
    }

// Body text
    public function setBodyText($content) {
        $this->_createAltPart();
        $content = str_replace(["\r", "\n"], ['', "\r\n"], $content);

        if($this->_bodyText === null) {
            $this->_bodyText = (new flow\mime\ContentPart($content))
                ->setContentType('text/plain')
                ->setEncoding(core\string\IEncoding::QP);

            $this->_altPart->prependPart($this->_bodyText);
        } else {
            $this->_bodyText->setContent($content);
        }

        return $this;
    }

    public function getBodyText() {
        return $this->_bodyText;
    }

    protected function _createAltPart() {
        if($this->_altPart !== null) {
            return false;
        }

        $this->_altPart = new flow\mime\MultiPart(flow\mime\IMultiPart::ALTERNATIVE);
        $this->prependPart($this->_altPart);
    }



// Attachments
    public function addFileAttachment($path, $fileName=null, $contentType=null) {
        $file = core\io\LocalFilePointer::factory($path);
        $pathName = basename($file->getPath());

        if(!$file->exists()) {
            throw new InvalidArgumentException(
                'Attachment file '.$pathName.' does not exist'
            );
        }

        if($fileName === null) {
            $fileName = $pathName;
        }

        if($contentType === null) {
            $contentType = $file->getContentType();
        }

        $part = $this->newContentPart($file)
            ->setContentType($contentType)
            ->setFileName($fileName, 'attachment');

        return $this;
    }

    public function addStringAttachment($string, $fileName, $contentType=null) {
        if($contentType === null) {
            $contentType = core\io\Type::fileToMime($fileName);
        }

        $part = $this->newContentPart($string)
            ->setContentType($contentType)
            ->setFileName($fileName, 'attachment');

        return $this;
    }


// Private
    public function isPrivate($flag=null) {
        if($flag !== null) {
            $this->_isPrivate = (bool)$flag;
            return $this;
        }

        return (bool)$this->_isPrivate;
    }

// From
    public function setFromAddress($address, $name=null) {
        $address = Address::factory($address, $name);

        if(!$address->isValid()) {
            throw new InvalidArgumentException(
                'From address '.(string)$address.' is invalid'
            );
        }

        $this->_from = $address;

        if(!$this->getReplyToAddress()) {
            $this->setReplyToAddress($this->_from);
        }

        return $this;
    }

    public function getFromAddress() {
        return $this->_from;
    }

    public function isFromAddressSet() {
        return $this->_from !== null;
    }

    public function isFromAddressValid() {
        return $this->_from && $this->_from->isValid();
    }


// To
    public function addToAddress($address, $name=null) {
        $address = Address::factory($address, $name);

        if(!$address->isValid()) {
            throw new InvalidArgumentException(
                'To address '.(string)$address.' is invalid'
            );
        }

        $this->_to[$address->getAddress()] = $address;
        return $this;
    }

    public function getToAddresses() {
        return $this->_to;
    }

    public function countToAddresses() {
        return count($this->_to);
    }

    public function hasToAddress($address) {
        if($address instanceof IAddress) {
            $address = $address->getAddress();
        }

        return isset($this->_to[$address]);
    }

    public function hasToAddresses() {
        return !empty($this->_to);
    }

    public function clearToAddresses() {
        $this->_to = array();
        return $this;
    }


// CC
    public function addCCAddress($address, $name=null) {
        $address = Address::factory($address, $name);

        if(!$address->isValid()) {
            throw new InvalidArgumentException(
                'CC address '.(string)$address.' is invalid'
            );
        }

        $this->_cc[$address->getAddress()] = $address;
        return $this;
    }

    public function getCCAddresses() {
        return $this->_cc;
    }

    public function countCCAddresses() {
        return count($this->_cc);
    }

    public function hasCCAddress($address) {
        if($address instanceof IAddress) {
            $address = $address->getAddress();
        }

        return isset($this->_cc[$address]);
    }

    public function hasCCAddresses() {
        return !empty($this->_cc);
    }

    public function clearCCAddresses() {
        $this->_cc = array();
        return $this;
    }


// BCC
    public function addBCCAddress($address, $name=null) {
        $address = Address::factory($address, $name);

        if(!$address->isValid()) {
            throw new InvalidArgumentException(
                'BCC address '.(string)$address.' is invalid'
            );
        }

        $this->_bcc[$address->getAddress()] = $address;
        return $this;
    }

    public function getBCCAddresses() {
        return $this->_bcc;
    }

    public function countBCCAddresses() {
        return count($this->_bcc);
    }

    public function hasBCCAddress($address) {
        if($address instanceof IAddress) {
            $address = $address->getAddress();
        }

        return isset($this->_bcc[$address]);
    }

    public function hasBCCAddresses() {
        return !empty($this->_bcc);
    }

    public function clearBCCAddresses() {
        $this->_bcc = array();
        return $this;
    }


// Reply to
    public function setReplyToAddress($address=null) {
        if($address === null) {
            $this->_headers->remove('reply-to');
        } else {
            $address = Address::factory($address);

            if(!$address->isValid()) {
                throw new InvalidArgumentException(
                    'Invalid reply-to address'
                );
            }

            $this->_headers->set('reply-to', $address->getAddress());
        }

        return $this;
    }

    public function getReplyToAddress() {
        $output = $this->_headers->get('reply-to');

        if($output !== null) {
            $output = new Address($output);
        }

        return $output;
    }

    public function setReturnPath($address=null) {
        if($address === null) {
            $this->_headers->remove('return-path');
        } else {
            $address = Address::factory($address);

            if(!$address->isValid()) {
                throw new InvalidArgumentException(
                    'Invalid return-path address'
                );
            }

            $this->_headers->set('return-path', $address->getAddress());
        }

        return $this;
    }

    public function getReturnPath() {
        $output = $this->_headers->get('return-path');

        if($output !== null) {
            $output = new Address($output);
        }

        return $output;
    }


// Headers
    public function prepareHeaders() {
        parent::prepareHeaders();
        $isWin = (0 === strpos(PHP_OS, 'WIN'));

        if($this->_from) {
            if($isWin) {
                $this->_headers->set('from', $this->_from->getAddress());
            } else {
                $this->_headers->set('from', $this->_from->toString());
            }
        }


        $to = array();
        $cc = array();
        $bcc = array();

        foreach($this->_to as $address) {
            if($isWin) {
                $to[] = $address->getAddress();
            } else {
                $to[] = $address->toString();
            }
        }

        foreach($this->_cc as $address) {
            if($isWin) {
                $cc[] = $address->getAddress();
            } else {
                $cc[] = $address->toString();
            }
        }

        foreach($this->_bcc as $address) {
            if($isWin) {
                $bcc[] = $address->getAddress();
            } else {
                $bcc[] = $address->toString();
            }
        }

        if(!empty($to)) {
            $this->_headers->set('to', implode(', ', $to));
        }

        if(!empty($cc)) {
            $this->_headers->set('cc', implode(', ', $cc));
        }

        if(!empty($bcc)) {
            $this->_headers->set('bcc', implode(', ', $bcc));
        }

        $this->_headers->set('MIME-Version', '1.0');
        return $this;
    }

    public function send(ITransport $transport=null) {
        if(!$transport) {
            $transport = flow\mail\transport\Base::factory();
        }

        try {
            return $transport->send($this);
        } catch(\Exception $e) {
            if($transport->getName() != 'Mail') {
                $transport = flow\mail\transport\Base::factory('Mail');
                return $transport->send($this);
            } else {
                throw $e;
            }
        }
    }
}