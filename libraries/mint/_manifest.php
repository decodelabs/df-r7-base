<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\mint;

use df;
use df\core;
use df\mint;
use df\user;


// Gateway
interface IGateway {
    public function getSupportedCurrencies(): array;
    public function isCurrencySupported($code): bool;

    public function submitCharge(IChargeRequest $charge): mint\IChargeResult;
    public function submitStandaloneCharge(IStandaloneChargeRequest $charge): IChargeResult;
    public function newStandaloneCharge(ICurrency $amount, ICreditCardReference $card, string $description=null, string $email=null);
}

interface ICaptureProviderGateway extends IGateway {
    public function authorizeCharge(IChargeRequest $charge): IChargeResult;
    public function authorizeStandaloneCharge(IStandaloneChargeRequest $charge): IChargeResult;
    public function captureCharge(IChargeCapture $charge);
    public function newChargeCapture(string $id);
}

interface IRefundProviderGateway extends IGateway {
    public function refundCharge(IChargeRefund $refund);
    public function newChargeRefund(string $id, ICurrency $amount=null);
}

interface ICustomerTrackingGateway extends IGateway {
    public function submitCustomerCharge(ICustomerChargeRequest $charge): IChargeResult;
    public function newCustomerCharge(ICurrency $amount, ICreditCardReference $card, string $customerId, string $description=null);

    public function addCustomer(string $email=null, string $description=null, ICreditCard $card=null): string;
    //public function updateCustomer(ICustomer $customer);
    public function deleteCustomer(string $customerId);
}

interface ICustomerTrackingCaptureProviderGateway extends ICaptureProviderGateway, ICustomerTrackingGateway {
    public function authorizeCustomerCharge(ICustomerChargeRequest $charge): IChargeResult;
}

interface ICardStoreGateway extends ICustomerTrackingGateway {
    public function addCard(string $customerId, ICreditCard $card);
    public function updateCard(string $customerId, string $cardId, ICreditCard $card);
    public function deleteCard(string $customerId, string $cardId, ICreditCard $card);
}

interface ISubscriptionProviderGateway extends ICustomerTrackingGateway {
    public function getPlans(): array;

    public function subscribeCustomer(string $planId, string $customerId);
    public function unsubscribeCustomer(string $planId, string $customerId);
}

interface ISubscriptionPlanControllerGateway extends ISubscriptionProviderGateway {
    public function addPlan();
    public function updatePlan();
    public function deletePlan();
}




// Credit card
interface ICreditCardReference {}

interface ICreditCard extends ICreditCardReference, core\IArrayProvider {
    public function setName(string $name);
    public function getName(): ?string;

    public static function isValidNumber(string $number): bool;
    public function setNumber(string $number);
    public function getNumber(): ?string;
    public function setLast4Digits(string $digits);
    public function getLast4Digits(): ?string;

    public static function getSupportedBrands(): array;
    public function getBrand(): ?string;

    public function setStartMonth(?int $month);
    public function getStartMonth(): ?int;
    public function setStartYear(?int $year);
    public function getStartYear(): ?int;
    public function setStartString(string $start);
    public function getStartString(): ?string;
    public function getStartDate(): ?core\time\IDate;

    public function setExpiryMonth(int $month);
    public function getExpiryMonth(): ?int;
    public function setExpiryYear(int $year);
    public function getExpiryYear(): ?int;
    public function setExpiryString(string $expiry);
    public function getExpiryString(): ?string;
    public function getExpiryDate(): ?core\time\IDate;

    public function setCvc(string $cvc);
    public function getCvc(): ?string;

    public function setIssueNumber(?string $number);
    public function getIssueNumber(): ?string;

    public function setBillingAddress(user\IPostalAddress $address=null);
    public function getBillingAddress(): ?user\IPostalAddress;

    public function isValid(): bool;
}

interface ICreditCardToken extends ICreditCardReference {

}



// Charge
interface IChargeRequest {
    public function setAmount(ICurrency $amount);
    public function getAmount(): ICurrency;
    public function setCard(ICreditCardReference $card);
    public function getCard(): ICreditCardReference;
    public function setDescription(?string $description);
    public function getDescription(): ?string;
}

interface IStandaloneChargeRequest extends IChargeRequest {
    public function setEmailAddress(?string $email);
    public function getEmailAddress(): ?string;
}

interface ICustomerChargeRequest extends IChargeRequest {
    public function setCustomerId(string $id);
    public function getCustomerId(): string;
}


interface IChargeCapture {
    public function setId(string $id);
    public function getId(): string;
}

interface IChargeResult {
    public function isSuccessful(bool $flag=null);
    public function isCardAccepted(bool $flag=null);
    public function isCardExpired(bool $flag=null);
    public function isCardUnavailable(bool $flag=null);
    public function isApiFailure(bool $flag=null);

    public function setMessage(?string $message);
    public function getMessage(): ?string;

    public function setInvalidFields(string ...$fields);
    public function addInvalidFields(string ...$fields);
    public function getInvalidFields(): array;

    public function setChargeId(?string $id);
    public function getChargeId(): ?string;

    public function setTransactionRecord($record);
    public function getTransactionRecord();
}

interface IChargeRefund {
    public function setId(string $id);
    public function getId(): string;
    public function setAmount(?ICurrency $amount);
    public function getAmount(): ?ICurrency;
}



// Customer
interface ICustomer {
    public function getId();
    public function getEmailAddress();
    public function getDescription();
}



// Currency
interface ICurrency extends core\IStringProvider {
    public function setAmount(float $amount);
    public function getAmount(): float;
    public function getIntegerAmount(): int;
    public function getFormattedAmount(): string;

    public function setCode(string $code);
    public function getCode(): string;

    public function convert(string $code, float $origRate, float $newRate);
    public function convertNew(string $code, float $origRate, float $newRate);
    public function hasRecognizedCode(): bool;
    public function getDecimalPlaces(): int;
    public function getDecimalFactor(): int;

    public function add($amount);
    public function addNew($amount): ICurrency;
    public function subtract($amount);
    public function subtractNew($amount): ICurrency;
    public function multiply(float $factor);
    public function multiplyNew(float $factor): ICurrency;
    public function divide(float $factor);
    public function divideNew(float $factor): ICurrency;
}