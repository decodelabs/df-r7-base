<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\payment\stripe2;

use df;
use df\core;
use df\spur;
use df\link;
use df\mint;
use df\user;

class Mediator implements IMediator {

    use spur\THttpMediator;

    const API_URL = 'https://api.stripe.com/v1/';
    const UPLOAD_URL = 'https://uploads.stripe.com/v1/';
    const VERSION = '2017-02-14';

    protected $_apiKey;

    public function __construct(string $apiKey) {
        $this->setApiKey($apiKey);
    }

// Api key
    public function setApiKey(string $key) {
        $this->_apiKey = $key;
        return $this;
    }

    public function getApiKey(): string {
        return $this->_apiKey;
    }



// Account
    public function fetchAccountDetails(): IData {
        $data = $this->requestJson('get', 'account');
        return new DataObject('primary_account', $data);
    }

    public function fetchCountrySpec(string $country): IData {
        $data = $this->requestJson('get', 'country_specs/'.$country);
        return new DataObject('country_spec', $data);
    }


### CORE RESOURCES


/***************
 Balance */

// Account balance
    public function fetchBalance(): IData {
        $data = $this->requestJson('get', 'balance');

        return new DataObject('balance', $data, function($data) {
            foreach($data->available as $i => $node) {
                $data->available->replace($i, new DataObject('available_balance', $node, [$this, '_processBalance']));
            }

            foreach($data->pending as $i => $node) {
                $data->pending->replace($i, new DataObject('pending_balance', $node, [$this, '_processBalance']));
            }
        });
    }

    protected function _processBalance(core\collection\ITree $data) {
        $data['amount'] = $this->_normalizeCurrency($data['amount'], $data['currency']);

        foreach($data->source_types as $node) {
            $node->setValue($this->_normalizeCurrency($node->getValue(), $data['currency']));
        }
    }


// Balance transactions
    public function fetchBalanceTransaction(string $id): IData {
        $data = $this->requestJson('get', 'balance/history/'.$id);
        return new DataObject('balance_transaction', $data, [$this, '_processBalanceTransaction']);
    }

    public function newBalanceTransactionFilter(string $type=null): IBalanceTransactionFilter {
        return new namespace\filter\BalanceTransaction($type);
    }

    public function fetchBalanceTransactions(IBalanceTransactionFilter $filter=null): IList {
        $data = $this->requestJson('get', 'balance/history', namespace\filter\BalanceTransaction::normalize($filter));
        return new DataList('balance_transaction', $filter, $data, [$this, '_processBalanceTransaction']);
    }

    protected function _processBalanceTransaction(core\collection\ITree $data) {
        $data['available_on'] = core\time\Date::factory($data['available_on']);
        $data['created'] = core\time\Date::factory($data['created']);

        $data['amount'] = $this->_normalizeCurrency($data['amount'], $data['currency']);
        $data['fee'] = $this->_normalizeCurrency($data['fee'], $data['currency']);
        $data['net'] = $this->_normalizeCurrency($data['net'], $data['currency']);

        foreach($data->fee_details as $i => $node) {
            $data->fee_details->replace($i, new DataObject($node['type'], $node, function($data) {
                $data['amount'] = $this->_normalizeCurrency($data['amount'], $data['currency']);
            }));
        }
    }




/***************
 Charges */


// Create charge
    public function newChargeCreateRequest(mint\ICurrency $amount, string $description=null): IChargeCreateRequest {
        return new namespace\request\ChargeCreate($amount, $description);
    }

    public function createCharge(IChargeCreateRequest $request): IData {
        $data = $this->requestJson('post', 'charges', $request->toArray());
        return (new DataObject('charge', $data, [$this, '_processCharge']))
            ->setRequest($request);
    }


// Fetch charge
    public function fetchCharge(string $id): IData {
        $data = $this->requestJson('get', 'charges/'.$id);
        return new DataObject('charge', $data, [$this, '_processCharge']);
    }



// Update charge
    public function newChargeUpdateRequest(string $id): IChargeUpdateRequest {
        return new namespace\request\ChargeUpdate($id);
    }

    public function updateCharge(IChargeUpdateRequest $request): IData {
        $data = $this->requestJson('post', 'charges/'.$request->getChargeId(), $request->toArray());
        return (new DataObject('charge', $data, [$this, '_processCharge']))
            ->setRequest($request);
    }



// List charges
    public function newChargeFilter(string $customerId=null): IChargeFilter {
        return new namespace\filter\Charge($customerId);
    }

    public function fetchCharges(IChargeFilter $filter=null): IList {
        $data = $this->requestJson('get', 'charges', namespace\filter\Charge::normalize($filter));
        return new DataList('charge', $filter, $data, [$this, '_processCharge']);
    }


// Capture charge
    public function newChargeCaptureRequest(string $chargeId, mint\ICurrency $amount=null): IChargeCaptureRequest {
        return new namespace\request\ChargeCapture($chargeId, $amount);
    }

    public function captureCharge(IChargeCaptureRequest $request): IData {
        $data = $this->requestJson('post', 'charges/'.$request->getChargeId().'/capture', $request->toArray());
        return (new DataObject('charge', $data, [$this, '_processCharge']))
            ->setRequest($request);
    }


// Process charge
    protected function _processCharge(core\collection\ITree $data) {
        $data['amount'] = $this->_normalizeCurrency($data['amount'], $data['currency']);
        $data['amount_refunded'] = $this->_normalizeCurrency($data['amount_refunded'], $data['currency']);
        $data['application_fee'] = $this->_normalizeCurrency($data['application_fee'], $data['currency']);
        $data['created'] = core\time\Date::factory($data['created']);

        $data->replace('refunds', new DataList('refund', null, $data->refunds, [$this, '_processRefund']));
        $data->replace('source', new DataObject('source', $data->source, [$this, '_processSource']));
    }



/***************
 Customers */

// Create customer
    public function newCustomerCreateRequest(string $emailAddress=null, string $description=null): ICustomerCreateRequest {
        return new namespace\request\CustomerCreate($emailAddress, $description);
    }

    public function createCustomer(ICustomerCreateRequest $request): IData {
        $data = $this->requestJson('post', 'customers', $request->toArray());
        return (new DataObject('customer', $data, [$this, '_processCustomer']))
            ->setRequest($request);
    }


// Fetch customer
    public function fetchCustomer(string $id): IData {
        $data = $this->requestJson('get', 'customers/'.$id);
        return (new DataObject('customer', $data, [$this, '_processCustomer']));
    }



// Update customer
    public function newCustomerUpdateRequest(string $id): ICustomerUpdateRequest {
        return new namespace\request\CustomerUpdate($id);
    }

    public function updateCustomer(ICustomerUpdateRequest $request): IData {
        $data = $this->requestJson('post', 'customers/'.$request->getCustomerId(), $request->toArray());
        return (new DataObject('customer', $data, [$this, '_processCustomer']))
            ->setRequest($request);
    }



// Delete customer
    public function deleteCustomer(string $id) {
        $this->requestJson('delete', 'customers/'.$id);
        return $this;
    }



// List customers
    public function newCustomerFilter(): ICustomerFilter {
        return new namespace\filter\Customer();
    }

    public function fetchCustomers(ICustomerFilter $filter=null): IList {
        $data = $this->requestJson('get', 'customers', namespace\filter\Customer::normalize($filter));
        return new DataList('customer', $filter, $data, [$this, '_processCustomer']);
    }


// Process customer
    protected function _processCustomer(core\collection\ITree $data) {
        $data['account_balance'] = $this->_normalizeCurrency($data['account_balance'], $data['currency']);
        $data['created'] = core\time\Date::factory($data['created']);

        if(!$data->discount->isEmpty()) {
            $data->replace('discount', new DataObject('discount', $data->discount, [$this, '_processDiscount']));
        }

        if(!$data->shipping->address->isEmpty()) {
            $address = $this->_normalizeAddress($data->shipping->address);
            $data->shipping->address->clear();
            $data->shipping->address->setValue($address);
        }

        $data->replace('sources', new DataList('source', null, $data->sources, [$this, '_processSource']));
        $data->replace('subscriptions', new DataList('subscription', null, $data->subscriptions, [$this, '_processSubscription']));
    }



/***************
 Disputes */

/*
// Fetch dispute
    public function fetchDispute(string $id): IData {
        core\stub();
    }


// Update dispute
    public function newDisputeUpdateRequest(string $id): IDisputeUpdateRequest {
        core\stub();
    }

    public function updateDispute(IDisputeUpdateRequest $request): IData {
        core\stub();
    }


// Close dispute
    public function closeDispute(string $id): IData {
        core\stub();
    }



// List disputes
    public function newDisputeFilter(): IDisputeFilter {
        core\stub();
    }

    public function fetchDisputes(IDisputeFilter $filter=null) {
        core\stub();
    }

*/



/***************
 Events */

/*
// Fetch event
    public function fetchEvent(string $id): IData {
        core\stub();
    }


// List events
    public function newEventFilter(): IEventFilter {
        core\stub();
    }

    public function fetchEvents(IEventFilter $filter=null) {
        core\stub();
    }

*/



/***************
 Files */

/*
// Upload file
    public function uploadFile(core\fs\IFile $file, string $purpose): IData {
        core\stub();
    }


// Fetch file
    public function fetchFileInfo(string $id): IData {
        core\stub();
    }



// List files
    public function newFileFilter(): IFileFilter {
        core\stub();
    }

    public function fetchFileInfos(IFileFilter $filter=null) {
        core\stub();
    }

*/



/***************
 Refunds */

/*
// Create refund
    public function newRefundCreateRequest(string $chargeId, string $reason=null): IRefundCreateRequest {
        core\stub();
    }

    public function createRefund(IRefundCreateRequest $request): IData {
        core\stub();
    }


// Fetch refund
    public function fetchRefund(string $id): IData {
        core\stub();
    }


// Update refund
    public function newRefundUpdateRequest(string $id): IRefundUpdateRequest {
        core\stub();
    }

    public function updateRefund(IRefundUpdateRequest $request) {
        core\stub();
    }


// List refunds
    public function newRefundFilter(string $chargeId=null): IRefundFilter {
        core\stub();
    }

    public function fetchRefunds(IRefundFilter $filter=null): IList {
        core\stub();
    }

*/

// Process refund
    protected function _processRefund(core\collection\ITree $data) {
        $data['amount'] = $this->_normalizeCurrency($data['amount'], $data['currency']);
        $data['created'] = core\time\Date::factory($data['created']);
    }




/***************
 Tokens */

/*
// Card token
    public function createCardToken(mint\ICreditCard $card, string $customerId=null): IData {
        core\stub();
    }

// Bank token
    public function createBankAccountToken(mint\IBankAccount $account, string $customerId=null): IData {
        core\stub();
    }

// Pii token
    public function createPiiToken(string $id): IData {
        core\stub();
    }


// Fetch token
    public function fetchToken(string $id): IData {
        core\stub();
    }

*/



/***************
 Transfers */

/*
// Create transfer
    public function newTransferCreateRequest(mint\ICurrency $amount, string $destinationId=null): ITransferCreateRequest {
        core\stub();
    }

    public function createTransfer(ITransferCreateRequest $request): IData {
        core\stub();
    }


// Fetch transfer
    public function fetchTransfer(string $id): IData {
        core\stub();
    }


// Update transfer
    public function newTransferUpdateRequest(string $id): ITransferUpdateRequest {
        core\stub();
    }

    public function updateTransfer(ITransferUpdateRequest $request): IData {
        core\stub();
    }


// List transfers
    public function newTransferFilter(string $recipient=null): ITransferFilter {
        core\stub();
    }

    public function fetchTransfers(ITransferFilter $filter=null): IList {
        core\stub();
    }
*/



/***************
 Transfer reversals */

/*
// Create reversal
    public function newTransferReversalCreateRequest(string $transferId): ITransferReversalCreateRequest {
        core\stub();
    }

    public function createTransferReversal(ITransferReversalCreateRequest $request): IData {
        core\stub();
    }


// Fetch reversal
    public function fetchTransferReversal(string $transferId, string $reversalId): IData {
        core\stub();
    }


// Update reversal
    public function newTransferReversalUpdateRequest(string $transferId, string $reversalId): ITransferReversalUpdateRequest {
        core\stub();
    }

    public function updateTransferReversal(ITransferReversalUpdateRequest $request): IData {
        core\stub();
    }


// List reversals
    public function newTransferReversalFilter(): ITransferReversalFilter {
        core\stub();
    }

    public function fetchTransferReversals(string $transferId, ITransferReversalFilter $filter=null): IList {
        core\stub();
    }

*/




### PAYMENT METHODS


/***************
 Alipay */
    //--



/***************
 Bank accounts */
    //--


/***************
 Cards */

// Create card
    public function newCardCreateRequest(string $customerId, $source): ICardCreateRequest {
        return new namespace\request\CardCreate($customerId, $source);
    }

    public function createCard(ICardCreateRequest $request): IData {
        $data = $this->requestJson('post', 'customers/'.$request->getCustomerId().'/sources', $request->toArray());
        return (new DataObject('source', $data, [$this, '_processSource']))
            ->setRequest($request);
    }


// Fetch card
    public function fetchCard(string $customerId, string $cardId) {
        $data = $this->requestJson('get', 'customers/'.$customerId.'/sources/'.$cardId);
        return (new DataObject('source', $data, [$this, '_processSource']));
    }


// Update card
    public function newCardUpdateRequest(string $customerId, string $cardId): ICardUpdateRequest {
        return new namespace\request\CardUpdate($customerId, $cardId);
    }

    public function updateCard(ICardUpdateRequest $request): IData {
        $data = $this->requestJson('post', 'customers/'.$request->getCustomerId().'/sources/'.$request->getCardId(), $request->toArray());
        return (new DataObject('source', $data, [$this, '_processSource']))
            ->setRequest($request);
    }



// Delete card
    public function deleteCard(string $customerId, string $cardId) {
        $this->requestJson('delete', 'customers/'.$customerId.'/sources/'.$cardId);
        return $this;
    }



// List cards
    public function newCardFilter(): ICardFilter {
        return new namespace\filter\Card();
    }

    public function fetchCards(string $customerId, ICardFilter $filter=null): IList {
        $data = $this->requestJson('get', 'customers/'.$customerId.'/sources', namespace\filter\Card::normalize($filter, ['object' => 'card']));
        return new DataList('source', $filter, $data, [$this, '_processSource']);
    }




/***************
 Sources */

// Process source
    protected function _processSource(core\collection\ITree $data) {
        switch($data['object']) {
            case 'card':
                $data->card = $this->_normalizeCard($data);
                break;
        }
    }



### RELAY
    //---






### SUBSCRIPTIONS

/***************
 Coupons */

/*
// Create coupon
    public function newCouponCreateRequest(string $id, string $type): ICouponCreateRequest {
        core\stub();
    }

    public function createCoupon(ICouponCreateRequest $request): IData {
        core\stub();
    }


// Fetch coupon
    public function fetchCoupon(string $id): IData {
        core\stub();
    }


// Update coupon
    public function newCouponUpdateRequest(string $id): ICouponUpdateRequest {
        core\stub();
    }

    public function updateCoupon(ICouponUpdateRequest $request): IData {
        core\stub();
    }


// Delete coupon
    public function deleteCoupon(string $id) {
        core\stub();
    }



// List coupons
    public function newCouponFilter(): ICouponFilter {
        core\stub();
    }

    public function fetchCoupons(ICouponFilter $filter=null): IList {
        core\stub();
    }
*/

// Process coupon
    protected function _processCoupon(core\collection\ITree $data) {
        $data['amount_off'] = $this->_normalizeCurrency($data['amount_off'], $data['currency']);
        $data['created'] = core\time\Date::factory($data['created']);
        $data['redeem_by'] = core\time\Date::factory($data['redeem_by']);
    }



/***************
 Discounts */

/*
// Delete customer discount
    public function deleteCustomerDiscount(string $customerId) {
        core\stub();
    }


// Delete subscription discount
    public function deleteSubscriptionDiscount(string $subscriptionId) {
        core\stub();
    }
*/

// Process discount
    protected function _processDiscount(core\collection\ITree $data) {
        $data['start'] = core\time\Date::factory($data['start']);
        $data['end'] = core\time\Date::factory($data['end']);

        $data->replace('coupon', new DataObject('coupon', $data->coupon, [$this, '_processCoupon']));
    }



/***************
 Invoices */

/*
// Create invoice
    public function newInvoiceCreateRequest(string $customerId, string $description=null): IInvoiceCreateRequest {
        core\stub();
    }

    public function createInvoice(IInvoiceCreateRequest $request): IData {
        core\stub();
    }


// Fetch invoice
    public function fetchInvoice(string $id): IData {
        core\stub();
    }



// List lines
    public function newInvoiceLineFilter(): IInvoiceLineFilter {
        core\stub();
    }

    public function fetchInvoiceLines(string $invoiceId, IInvoiceLineFilter $filter=null): IList {
        core\stub();
    }


// Preview invoice
    public function newInvoicePreviewRequest(string $customerId): IInvoicePreviewRequest {
        core\stub();
    }

    public function previewInvoice(IInvoicePreviewRequest $request): IData {
        core\stub();
    }



// Update invoice
    public function newInvoiceUpdateRequest(string $id): IInvoiceUpdateRequest {
        core\stub();
    }

    public function updateInvoice(IInvoiceUpdateRequest $request): IData {
        core\stub();
    }



// Pay invoice
    public function payInvoice(string $id): IData {
        core\stub();
    }



// List invoices
    public function newInvoiceFilter(string $customerId=null): IInvoiceFilter {
        core\stub();
    }

    public function fetchInvoices(IInvoiceFilter $filter=null): IList {
        core\stub();
    }

*/



/***************
 Invoice items */

/*
// Create item
    public function newInvoiceItemCreateRequest(string $customerId, mint\ICurrency $amount): IInvoiceItemCreateRequest {
        core\stub();
    }

    public function createInvoiceItem(IInvoiceItemCreateRequest $request): IData {
        core\stub();
    }


// Fetch item
    public function fetchInvoiceItem(string $id): IData {
        core\stub();
    }


// Update item
    public function newInvoiceItemUpdateRequest(string $id): IInvoiceItemUpdateRequest {
        core\stub();
    }

    public function updateInvoiceItem(IInvoiceItemUpdateRequest $request): IData {
        core\stub();
    }


// Delete item
    public function deleteInvoiceItem(string $id) {
        core\stub();
    }


// List items
    public function newInvoiceItemFilter(string $customerId=null): IInvoiceItemFilter {
        core\stub();
    }

    public function fetchInvoiceItems(IInvoiceItemFilter $filter=null): IList {
        core\stub();
    }
*/




/***************
 Plans */

// Create plan
    public function newPlanCreateRequest(string $id, string $name, mint\ICurrency $amount, string $interval='month'): IPlanCreateRequest {
        return new namespace\request\PlanCreate($id, $name, $amount, $interval);
    }

    public function createPlan(IPlanCreateRequest $request): IData {
        $data = $this->requestJson('post', 'plans', $request->toArray());
        return (new DataObject('plan', $data, [$this, '_processPlan']))
            ->setRequest($request);
    }


// Fetch plan
    public function fetchPlan(string $id): IData {
        $data = $this->requestJson('get', 'plans/'.$id);
        return new DataObject('plan', $data, [$this, '_processPlan']);
    }



// Update plan
    public function newPlanUpdateRequest(string $id): IPlanUpdateRequest {
        return new namespace\request\PlanUpdate($id);
    }

    public function updatePlan(IPlanUpdateRequest $request): IData {
        $data = $this->requestJson('post', 'plans/'.$request->getPlanId(), $request->toArray());
        return (new DataObject('plan', $data, [$this, '_processPlan']))
            ->setRequest($request);
    }


// Delete plan
    public function deletePlan(string $id) {
        $this->requestJson('delete', 'plans/'.$id);
        return $this;
    }


// List plans
    public function newPlanFilter(): IPlanFilter {
        return new namespace\filter\Plan();
    }

    public function fetchPlans(IPlanFilter $filter=null): IList {
        $data = $this->requestJson('get', 'plans', namespace\filter\Plan::normalize($filter));
        return new DataList('plan', $filter, $data, [$this, '_processPlan']);
    }


// Process plan
    protected function _processPlan(core\collection\ITree $data) {
        $data['amount'] = $this->_normalizeCurrency($data['amount'], $data['currency']);
        $data['created'] = core\time\Date::factory($data['created']);
    }





/***************
 Subscriptions */

// Create subscription
    public function newSubscriptionCreateRequest(string $customerId, string $planId=null): ISubscriptionCreateRequest {
        core\stub();
    }

    public function createSubscription(ISubscriptionCreateRequest $request): IData {
        core\stub();
    }


// Fetch subscription
    public function fetchSubscription(string $id): IData {
        core\stub();
    }


// Update subscription
    public function newSubscriptionUpdateRequest(string $id): ISubscriptionUpdateRequest {
        core\stub();
    }

    public function updateSubscription(ISubscriptionUpdateRequest $request): IData {
        core\stub();
    }


// Cancel subscription
    public function cancelSubscription(string $id, bool $atPeriodEnd=false): IData {
        core\stub();
    }



// List subscriptions
    public function newSubscriptionFilter(string $customerId=null): ISubscriptionFilter {
        core\stub();
    }

    public function fetchSubscriptions(ISubscriptionFilter $filter=null) {
        core\stub();
    }


// Process subscription
    protected function _processSubscription(core\collection\ITree $data) {
        $data['canceled_at'] = core\time\Date::factory($data['canceled_at']);
        $data['created'] = core\time\Date::factory($data['created']);
        $data['current_period_start'] = core\time\Date::factory($data['current_period_start']);
        $data['current_period_end'] = core\time\Date::factory($data['current_period_end']);
        $data['ended_at'] = core\time\Date::factory($data['ended_at']);
        $data['start'] = core\time\Date::factory($data['start']);
        $data['trial_start'] = core\time\Date::factory($data['trial_start']);
        $data['trial_end'] = core\time\Date::factory($data['trial_end']);

        if(!$data->discount->isEmpty()) {
            $data->replace('discount', new DataObject('discount', $data->discount, [$this, '_processDiscount']));
        }

        $data->replace('items', new DataList('subscription_item', null, $data->items, [$this, '_processSubscriptionItem']));
        $data->replace('plan', new DataObject('plan', $data->plan, [$this, '_processPlan']));
    }




/***************
 Subscription items */

/*
// Create item
    public function newSubscriptionItemCreateRequest(string $subscriptionId, string $planId): ISubscriptionItemCreateRequest {
        core\stub();
    }

    public function createSubscriptionItem(ISubscriptionItemCreateRequest $request): IData {
        core\stub();
    }


// Fetch item
    public function fetchSubscriptionId(string $id): IData {
        core\stub();
    }


// Update item
    public function newSubscriptionItemUpdateRequest(string $id): ISubscriptionItemUpdateRequest {
        core\stub();
    }

    public function updateSubscriptionItem(ISubscriptionItemUpdateRequest $request): IData {
        core\stub();
    }


// Delete item
    public function newSubscriptionItemDeleteRequest(string $id): ISubscriptionItemDeleteRequest {
        core\stub();
    }

    public function deleteSubscriptionItem(ISubscriptionItemDeleteRequest $request): IData {
        core\stub();
    }


// List items
    public function newSubscriptionItemFilter(): ISubscriptionItemFilter {
        core\stub();
    }

    public function fetchSubscriptionItems(string $subscriptionId, ISubscriptionItemFilter $filter=null): IList {
        core\stub();
    }
*/


// Process item
    protected function _processSubscriptionItem(core\collection\ITree $data) {
        $data['created'] = core\time\Date::factory($data['created']);
        $data->replace('plan', new DataObject('plan', $data->plan, [$this, '_processPlan']));
    }



### HELPERS
    public static function cardToArray(mint\ICreditCard $card): array {
        $output = [];

        $output['number'] = $card->getNumber();
        $output['exp_month'] = $card->getExpiryMonth();
        $output['exp_year'] = $card->getExpiryYear();

        if(null !== ($cvc = $card->getCvc())) {
            $output['cvc'] = $cvc;
        }

        if(null !== ($name = $card->getName())) {
            $output['name'] = $name;
        }

        if($address = $card->getBillingAddress()) {
            $output['address_line1'] = $address->getMainStreetLine();
            $output['address_line2'] = $address->getExtendedStreetLine();
            $output['address_city'] = $address->getLocality();
            $output['address_state'] = $address->getRegion();
            $output['address_zip'] = $address->getPostalCode();
            $output['address_country'] = $address->getCountryName();
        }

        return $output;
    }

    public static function addressToArray(user\IPostalAddress $address): array {
        return [
            'line1' => $address->getStreetLine1(),
            'line2' => $address->getStreetLine2(),
            'city' => $address->getLocality(),
            'state' => $address->getRegion(),
            'postal_code' => $address->getPostalCode(),
            'country' => $address->getCountryCode()
        ];
    }

    protected function _normalizeCurrency(/*?int*/ $amount, /*?string*/ $currency)/*: ?mint\ICurrency*/ {
        if($amount === null) {
            return null;
        }

        return mint\Currency::fromIntegerAmount($amount, $currency);
    }

    protected function _normalizeCard(core\collection\ITree $data) {
        $card = mint\CreditCard::fromArray([
            'name' => $data['name'],
            'last4' => $data['last4'],
            'expiryMonth' => $data['exp_month'],
            'expiryYear' => $data['exp_year']
        ]);

        if($data['address_city'] !== null) {
            $card->setBillingAddress(user\PostalAddress::fromArray([
                'street1' => $data['address_line1'],
                'street2' => $data['address_line2'],
                'locality' => $data['address_city'],
                'region' => $data['address_state'],
                'postalCode' => $data['address_zip'],
                'countryCode' => $data['address_country']
            ]));
        }

        return $card;
    }

    protected function _normalizeAddress(core\collection\ITree $data) {
        if($data['city'] === null) {
            return null;
        }

        return user\PostalAddress::fromArray([
            'street1' => $data['line1'],
            'street2' => $data['line2'],
            'locality' => $data['city'],
            'region' => $data['state'],
            'postalCode' => $data['postal_code'],
            'countryCode' => $data['country']
        ]);
    }


### IO
    public function createUrl($path) {
        $base = self::API_URL;
        return link\http\Url::factory($base.ltrim($path, '/'));
    }

    protected function _prepareRequest(link\http\IRequest $request) {
        $request->options->setSecureTransport('tls');
        $request->headers->set('Authorization', 'Bearer '.$this->_apiKey);
        $request->headers->set('Stripe-Version', self::VERSION);
    }

    protected function _extractResponseError(link\http\IResponse $response) {
        $data = $response->getJsonContent();
        $message = $data->error->get('message', 'Request failed');
        $data = $data->error->toArray();
        $code = $response->getHeaders()->getStatusCode();

        switch($data['type']) {
            case 'api_connection_error':
            case 'api_error':
            case 'authentication_error':
            case 'invalid_request_error':
                $errorType = 'EApi,EImplementation';
                break;

            case 'card_error':
                $errorType = 'EApi,ECard';
                break;

            case 'rate_limit_error':
                $errorType = 'EApi,ERateLimit';
                break;

            default:
                $errorType = 'EApi';
                break;
        }

        return core\Error::{$errorType}([
            'message' => $message,
            'data' => $data,
            'code' => $code
        ]);
    }
}