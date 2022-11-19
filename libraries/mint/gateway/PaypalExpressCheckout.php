<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\mint\gateway;

use DecodeLabs\Glitch;

use df\mint;

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
