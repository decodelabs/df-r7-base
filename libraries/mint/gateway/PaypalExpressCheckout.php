<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\mint\gateway;

use df;
use df\core;
use df\mint;
    
class PaypalExpressCheckout extends Base {

    public function getSupportedCurrencies() {
        return [];
    }

    public function submitCharge(df\mint\ICharge $charge) {
        core\stub($charge);
    }
}