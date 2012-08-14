<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\mail;

use df;
use df\core;
    
class Message extends core\mime\MultiPart implements IMessage {

	protected $_from;
	protected $_to = array();
	protected $_cc = array();
	protected $_bcc = array();

	protected $_altPart;
	protected $_bodyText;
	protected $_bodyHtml;
	protected $_isPrivate = false;

    public function setSubject($subject) {
    	$this->_headers->set('subject', (string)$subject);
    	return $this;
    }

	public function getSubject() {
		return $this->_headers->get('subject');
	}

	public function setBodyHtml($content) {
		$this->_createAltPart();

		if($this->_bodyHtml === null) {
			$this->_bodyHtml = (new core\mime\ContentPart($content))
				->setContentType('text/html')
				->setEncoding(core\mime\IMessageEncoding::QP);

			$this->_altPart->addPart($this->_bodyHtml);
		} else {
			$this->_bodyHtml->setContent($content);
		}

		return $this->_bodyHtml;
	}

	public function getBodyHtml() {
		return $this->_bodyHtml;
	}

	public function setBodyText($content) {
		$this->_createAltPart();
		$content = str_replace(["\r", "\n"], ['', "\r\n"], $content);

		if($this->_bodyText === null) {
			$this->_bodyText = (new core\mime\ContentPart($content))
				->setContentType('text/plain')
				->setEncoding(core\mime\IMessageEncoding::QP);

			$this->_altPart->prependPart($this->_bodyText);
		} else {
			$this->_bodyText->setContent($content);
		}

		return $this->_bodyText;
	}

	public function getBodyText() {
		return $this->_bodyText;
	}

	protected function _createAltPart() {
		if($this->_altPart !== null) {
			return false;
		}

		$this->_altPart = new core\mime\MultiPart(core\mime\IMultiPart::ALTERNATIVE);
		$this->_altPart->isMessage(false);
		$this->prependPart($this->_altPart);
	}

	public function isPrivate($flag=null) {
		if($flag !== null) {
			$this->_isPrivate = (bool)$flag;
			return $this;
		}

		return (bool)$this->_isPrivate;
	}

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


	public function prepareHeaders() {
		parent::prepareHeaders();

		if($this->_from) {
			if($isWin = 0 === strpos(PHP_OS, 'WIN')) {
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

		return $this;
	}

	public function send(ITransport $transport=null) {
		if(!$transport) {
			$transport = core\mail\transport\Base::factory();
		}

		return $transport->send($this);
	}
}