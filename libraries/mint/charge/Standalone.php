<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\mint\charge;

use df;
use df\core;
use df\mint;

class Standalone extends Base implements mint\IStandaloneChargeRequest {

    protected $_email;

    public function __construct(mint\ICurrency $amount, mint\ICreditCardReference $card, string $description=null, string $email=null) {
        parent::__construct($amount, $card, $description);
        $this->setEmailAddress($email);
    }

    public function setEmailAddress(?string $email) {
        $this->_email = $email;
        return $this;
    }

    public function getEmailAddress(): ?string {
        return $this->_email;
    }
}