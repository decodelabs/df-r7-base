<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\payment\stripe;

use df;
use df\core;
use df\spur;
use df\mint;
    

// Exceptions
interface IException {}
class BadMethodCallException extends \BadMethodCallException implements IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}



// Interfaces
interface IMediator {
    public function getHttpClient();

// Api key
    public function setApiKey($key);
    public function getApiKey();


// Charges
    public function newCharge($amount, mint\ICreditCardReference $card, $description=null);
    public function submitCharge(ICharge $charge);

// IO
    public function callServer($path, array $data=array(), $method='post');
}




interface ICharge {
    public function setAmount($amount);
    public function getAmount();
    public function setCustomerId($id);
    public function getCustomerId();
    public function setCard(mint\ICreditCardReference $card);
    public function getCard();
    public function setDescription($description);
    public function getDescription();
    public function shouldCapture($flag=null);
    public function setApplicationFee($amount);
    public function getApplicationFee();
}