<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\auth\recaptcha;

use df;
use df\core;
use df\spur;

class Result implements IResult {

    public $success = false;
    public $codes = [];

    public static function factory(core\collection\ITree $data) {
        return new self($data['success'], $data->{'error-codes'}->toArray());
    }

    public function __construct(bool $success, array $errorCodes=[]) {
        $this->success = $success;
        $this->codes = $errorCodes;
    }

    public function isSuccess(): bool {
        return (bool)$this->success;
    }

    public function getErrorCodes(): array {
        return (array)$this->codes;
    }
}