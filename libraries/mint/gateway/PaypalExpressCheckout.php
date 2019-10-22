<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\mint\gateway;

use df;
use df\core;
use df\mint;

use DecodeLabs\Glitch;

class PaypalExpressCheckout extends Base
{
    public function getSupportedCurrencies(): array
    {
        return [];
    }

    public function submitStandaloneCharge(mint\IChargeRequest $charge): string
    {
        Glitch::incomplete($charge);
    }
}
