<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\mint;

use DecodeLabs\Exceptional;
use df\core;
use df\mesh;
use df\mint;

use df\user;

// Gateway
interface IGateway
{
    public function isTesting(): bool;
    public function getApiKey(): ?string;
    public function getPublicKey(): ?string;

    public function getSupportedCurrencies(): array;
    public function isCurrencySupported($code): bool;

    public function getApiIps(): ?array;
    public function getWebhookIps(): ?array;

    public function submitCharge(IChargeRequest $charge): string;
    public function submitStandaloneCharge(IChargeRequest $charge): string;
    public function newStandaloneCharge(ICurrency $amount, ICreditCardReference $card, string $description = null, string $email = null): IChargeRequest;
}

interface ICaptureProviderGateway extends IGateway
{
    public function authorizeCharge(IChargeRequest $charge): string;
    public function authorizeStandaloneCharge(IChargeRequest $charge): string;
    public function captureCharge(IChargeCapture $charge): string;
    public function newChargeCapture(string $id): IChargeCapture;
}

trait TCaptureProviderGateway
{
    public function authorizeCharge(IChargeRequest $charge): string
    {
        if ($charge instanceof ICustomerChargeRequest
        && $this instanceof ICustomerTrackingCaptureProviderGateway) {
            return $this->authorizeCustomerCharge($charge);
        } elseif ($charge instanceof IChargeRequest
        && $this instanceof ICaptureProviderGateway) {
            return $this->authorizeStandaloneCharge($charge);
        } else {
            throw Exceptional::{'Charge,InvalidArgument'}([
                'message' => 'Gateway doesn\'t support authorizing this type of charge',
                'data' => $charge
            ]);
        }
    }

    public function newChargeCapture(string $id): IChargeCapture
    {
        return new mint\charge\Capture($id);
    }
}

interface IRefundProviderGateway extends IGateway
{
    public function refundCharge(IChargeRefund $refund): string;
    public function newChargeRefund(string $id, ICurrency $amount = null): IChargeRefund;
}

trait TRefundProviderGateway
{
    public function newChargeRefund(string $id, ICurrency $amount = null): IChargeRefund
    {
        return new mint\charge\Refund($id, $amount);
    }
}

interface ICustomerTrackingGateway extends IGateway
{
    public function submitCustomerCharge(ICustomerChargeRequest $charge): string;
    public function newCustomerCharge(ICurrency $amount, ICreditCardReference $card, string $customerId, string $description = null): ICustomerChargeRequest;

    public function newCustomer(string $email = null, string $description = null, ICreditCard $card = null): ICustomer;
    public function fetchCustomer(string $id): ICustomer;

    public function addCustomer(ICustomer $customer): ICustomer;
    public function updateCustomer(ICustomer $customer): ICustomer;
    public function deleteCustomer(string $customerId);
}

trait TCustomerTrackingGateway
{
    public function newCustomerCharge(mint\ICurrency $amount, mint\ICreditCardReference $card, string $customerId, string $description = null): ICustomerChargeRequest
    {
        return new mint\charge\CustomerRequest($amount, $card, $customerId, $description);
    }

    public function newCustomer(string $email = null, string $description = null, ICreditCard $card = null): ICustomer
    {
        return new mint\Customer(null, $email, $description, $card);
    }
}

interface ICustomerTrackingCaptureProviderGateway extends ICaptureProviderGateway, ICustomerTrackingGateway
{
    public function authorizeCustomerCharge(ICustomerChargeRequest $charge): string;
}

interface ICardStoreGateway extends ICustomerTrackingGateway
{
    /*
    public function addCard(string $customerId, ICreditCard $card);
    public function updateCard(string $customerId, string $cardId, ICreditCard $card);
    public function deleteCard(string $customerId, string $cardId, ICreditCard $card);
     */
}

interface ISubscriptionProviderGateway extends ICustomerTrackingGateway
{
    public function getPlans(): array;
    public function newSubscription(string $customerId, string $planId): ISubscription;
    public function fetchSubscription(string $subscriptionId): ISubscription;
    public function getSubscriptionsFor(ICustomer $customer): array;

    public function subscribeCustomer(ISubscription $subscription): ISubscription;
    public function updateSubscription(ISubscription $subscription): ISubscription;
    public function endSubscriptionTrial(string $subscriptionId, int $inDays = null): ISubscription;
    public function cancelSubscription(string $subscriptionId, bool $atPeriodEnd = false): ISubscription;
}

trait TSubscriptionProviderGateway
{
    public function newSubscription(string $customerId, string $planId): ISubscription
    {
        return new mint\Subscription(null, $customerId, $planId);
    }
}

interface ISubscriptionPlanControllerGateway extends ISubscriptionProviderGateway
{
    public function syncPlans(iterable $local = []): \Generator;
    public function newPlan(string $id, string $name, mint\ICurrency $amount, string $interval = 'month');

    public function addPlan(IPlan $plan): IPlan;
    public function updatePlan(IPlan $plan): IPlan;
    public function deletePlan(string $planId);
    public function clearPlanCache();
}

trait TSubscriptionPlanControllerGateway
{
    public function syncPlans(iterable $local = []): \Generator
    {
        $planList = [];
        $this->clearPlanCache();

        foreach ($this->getPlans() as $plan) {
            $planList[$plan->getId()] = $plan;
        }

        foreach ($local as $plan) {
            if (isset($planList[$plan->getId()])) {
                $remote = $planList[$plan->getId()];

                if ($plan->shouldUpdate($remote)) {
                    $plan = $this->updatePlan($plan);
                    $action = 'update';
                } else {
                    $action = 'match';
                }

                unset($planList[$plan->getId()]);
                yield $action => $plan;
            } else {
                yield 'export' => $this->addPlan($plan);
            }
        }

        foreach ($planList as $plan) {
            yield 'import' => $plan;
        }
    }

    public function newPlan(string $id, string $name, mint\ICurrency $amount, string $interval = 'month')
    {
        return new mint\Plan($id, $name, $amount, $interval);
    }
}




// Credit card
interface ICreditCardReference
{
}

interface ICreditCard extends ICreditCardReference, core\IArrayProvider
{
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

    public function setBillingAddress(user\IPostalAddress $address = null);
    public function getBillingAddress(): ?user\IPostalAddress;

    public function isValid(): bool;
}

interface ICreditCardToken extends ICreditCardReference
{
    public function getToken(): string;
}



// Charge
interface IChargeRequest
{
    public function setAmount(ICurrency $amount);
    public function getAmount(): ICurrency;
    public function setCard(ICreditCardReference $card);
    public function getCard(): ICreditCardReference;
    public function setDescription(?string $description);
    public function getDescription(): ?string;
    public function setEmailAddress(?string $email);
    public function getEmailAddress(): ?string;
}

interface ICustomerChargeRequest extends IChargeRequest
{
    public function setCustomerId(string $id);
    public function getCustomerId(): string;
}


interface IChargeCapture
{
    public function setId(string $id);
    public function getId(): string;
}


interface IChargeRefund
{
    public function setId(string $id);
    public function getId(): string;
    public function setAmount(?ICurrency $amount);
    public function getAmount(): ?ICurrency;
}



// Customer
interface ICustomer
{
    public function setId(?string $id);
    public function getId(): ?string;
    public function setLocalId(?string $id);
    public function getLocalId(): ?string;

    public function setEmailAddress(?string $email);
    public function getEmailAddress(): ?string;
    public function setDescription(?string $description);
    public function getDescription(): ?string;

    // shipping

    public function setCard(?ICreditCard $card);
    public function getCard(): ?ICreditCard;

    public function setUserId(?string $userId);
    public function getUserId(): ?string;

    public function isDelinquent(bool $flag = null);

    public function setCachedSubscriptions(?array $subscriptions);
    public function getCachedSubscriptions(): ?array;
    public function hasSubscriptionCache(): bool;
}



// Plan
interface IPlan
{
    public function setId(string $id);
    public function getId(): string;
    public function setAmount(ICurrency $amount);
    public function getAmount(): ICurrency;
    public function setName(string $name);
    public function getName(): string;
    public function setInterval(string $interval, int $count = null);
    public function getInterval();
    public function setIntervalCount(int $count);
    public function getIntervalCount();
    public function setStatementDescriptor(?string $descriptor);
    public function getStatementDescriptor(): ?string;
    public function setTrialDays(?int $days);
    public function getTrialDays(): ?int;

    public function shouldUpdate(IPlan $plan): bool;
}


// Subscriptions
interface ISubscription
{
    public function setId(?string $id);
    public function getId(): ?string;
    public function setLocalId(?string $id);
    public function getLocalId(): ?string;

    public function setCustomerId(string $customerId);
    public function getCustomerId(): string;

    public function setPlanId(string $planId);
    public function getPlanId(): string;

    public function setTrialStart($date);
    public function getTrialStart(): ?core\time\IDate;
    public function setTrialEnd($date);
    public function getTrialEnd(): ?core\time\IDate;

    public function setPeriodStart($date);
    public function getPeriodStart(): ?core\time\IDate;
    public function setPeriodEnd($date);
    public function getPeriodEnd(): ?core\time\IDate;

    public function setStartDate($date);
    public function getStartDate(): ?core\time\IDate;
    public function setEndDate($date);
    public function getEndDate(): ?core\time\IDate;
    public function setCancelDate($date, bool $atPeriodEnd = false);
    public function getCancelDate(): ?core\time\IDate;
    public function willCancelAtPeriodEnd(): bool;

    // Coupon
}




// Currency
interface ICurrency extends core\IStringProvider
{
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



// Events
interface IEvent extends mesh\entity\IEntity, core\collection\ITree
{
    public function getSource(): string;
    public function getAction(): string;
}
