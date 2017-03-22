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


// Balance
    public function fetchBalance(): IData {
        $data = $this->requestJson('get', 'balance');

        return new DataObject('balance', $data, function($data) {
            foreach($data->available as $i => $node) {
                $data->available->replace($i, new DataObject('available_balance', $node, [$this, '_normalizeBalance']));
            }

            foreach($data->pending as $i => $node) {
                $data->pending->replace($i, new DataObject('pending_balance', $node, [$this, '_normalizeBalance']));
            }
        });
    }

    protected function _normalizeBalance(core\collection\ITree $data) {
        $data['amount'] = mint\Currency::fromIntegerAmount($data['amount'], $data['currency']);

        foreach($data->source_types as $node) {
            $node->setValue(mint\Currency::fromIntegerAmount($node->getValue(), $data['currency']));
        }
    }

    public function fetchBalanceTransaction(string $id): IData {
        $data = $this->requestJson('get', 'balance/history/'.$id);
        return new DataObject('balance_transaction', $data, [$this, '_normalizeBalanceTransaction']);
    }

    public function newBalanceTransactionFilter(string $type=null): IBalanceTransactionFilter {
        return new namespace\filter\BalanceTransaction($type);
    }

    public function fetchBalanceTransactions(IBalanceTransactionFilter $filter=null): IList {
        $data = $this->requestJson('get', 'balance/history', namespace\filter\BalanceTransaction::normalize($filter));
        return new DataList('balance_transaction', $filter, $data, [$this, '_normalizeBalanceTransaction']);
    }

    protected function _normalizeBalanceTransaction(core\collection\ITree $data) {
        $data['available_on'] = core\time\Date::factory($data['available_on']);
        $data['created'] = core\time\Date::factory($data['created']);

        $data['amount'] = mint\Currency::fromIntegerAmount($data['amount'], $data['currency']);
        $data['fee'] = mint\Currency::fromIntegerAmount($data['fee'], $data['currency']);
        $data['net'] = mint\Currency::fromIntegerAmount($data['net'], $data['currency']);

        foreach($data->fee_details as $i => $node) {
            $data->fee_details->replace($i, new DataObject($node['type'], $node, function($data) {
                $data['amount'] = mint\Currency::fromIntegerAmount($data['amount'], $data['currency']);
            }));
        }
    }




// Charges
    public function newChargeCreateRequest(mint\ICurrency $amount, string $description=null): IChargeCreateRequest {
        return new namespace\request\Charge_Create($amount, $description);
    }

    public function createCharge(IChargeCreateRequest $request): IData {
        core\stub();
    }

    public function fetchCharge(string $id): IData {
        core\stub();
    }


    public function newChargeUpdateRequest(string $id): IChargeUpdateRequest {
        return new namespace\request\Charge_Update($id);
    }

    public function updateCharge(IChargeUpdateRequest $request): IData {
        core\stub();
    }


    public function newChargeFilter(string $customerId=null): IChargeFilter {
        core\stub();
    }

    public function fetchCharges(IChargeFilter $filter=null): IList {
        core\stub();
    }


    public function newCaptureRequest(string $chargeId, mint\ICurrency $amount=null): IChargeCaptureRequest {
        return new namespace\request\Charge_Capture($chargeId, $amount);
    }

    public function captureCharge(IChargeCaptureRequest $request): IData {
        core\stub();
    }



// Customers
    public function newCustomerCreateRequest(string $emailAddress=null, string $description=null): ICustomerCreateRequest {
        core\stub();
    }

    public function createCustomer(ICustomerCreateRequest $request): IData {
        core\stub();
    }

    public function fetchCustomer(string $id): IData {
        core\stub();
    }


    public function newCustomerUpdateRequest(string $id): ICustomerUpdateRequest {
        core\stub();
    }

    public function updateCustomer(ICustomerUpdateRequest $request): IData {
        core\stub();
    }


    public function deleteCustomer(string $id) {
        core\stub();
    }


    public function newCustomerFilter(): ICustomerFilter {
        core\stub();
    }

    public function fetchCustomers(ICustomerFilter $filter=null): IList {
        core\stub();
    }




// Disputes
/*
    public function fetchDispute(string $id): IData {
        core\stub();
    }


    public function newDisputeUpdateRequest(string $id): IDisputeUpdateRequest {
        core\stub();
    }

    public function updateDispute(IDisputeUpdateRequest $request): IData {
        core\stub();
    }


    public function closeDispute(string $id): IData {
        core\stub();
    }


    public function newDisputeFilter(): IDisputeFilter {
        core\stub();
    }

    public function fetchDisputes(IDisputeFilter $filter=null) {
        core\stub();
    }

*/



// Events
/*
    public function fetchEvent(string $id): IData {
        core\stub();
    }


    public function newEventFilter(): IEventFilter {
        core\stub();
    }

    public function fetchEvents(IEventFilter $filter=null) {
        core\stub();
    }

*/



// Files
/*
    public function uploadFile(core\fs\IFile $file, string $purpose): IData {
        core\stub();
    }

    public function fetchFileInfo(string $id): IData {
        core\stub();
    }


    public function newFileFilter(): IFileFilter {
        core\stub();
    }

    public function fetchFileInfos(IFileFilter $filter=null) {
        core\stub();
    }

*/



// Refunds
/*
    public function newRefundCreateRequest(string $chargeId, string $reason=null): IRefundCreateRequest {
        core\stub();
    }

    public function createRefund(IRefundCreateRequest $request): IData {
        core\stub();
    }

    public function fetchRefund(string $id): IData {
        core\stub();
    }


    public function newRefundUpdateRequest(string $id): IRefundUpdateRequest {
        core\stub();
    }

    public function updateRefund(IRefundUpdateRequest $request) {
        core\stub();
    }


    public function newRefundFilter(string $chargeId=null): IRefundFilter {
        core\stub();
    }

    public function fetchRefunds(IRefundFilter $filter=null): IList {
        core\stub();
    }

*/



// Tokens
/*
    public function createCardToken(mint\ICreditCard $card, string $customerId=null): IData {
        core\stub();
    }

    //public function createBankAccountToken(mint\IBankAccount $account, string $customerId=null): IData {
        core\stub();
    }

    //public function createPiiToken(string $id): IData {
        core\stub();
    }

    public function fetchToken(string $id): IData {
        core\stub();
    }

*/



// Transfers
/*
    public function newTransferCreateRequest(mint\ICurrency $amount, string $destinationId=null): ITransferCreateRequest {
        core\stub();
    }

    public function createTransfer(ITransferCreateRequest $request): IData {
        core\stub();
    }

    public function fetchTransfer(string $id): IData {
        core\stub();
    }


    public function newTransferUpdateRequest(string $id): ITransferUpdateRequest {
        core\stub();
    }

    public function updateTransfer(ITransferUpdateRequest $request): IData {
        core\stub();
    }


    public function newTransferFilter(string $recipient=null): ITransferFilter {
        core\stub();
    }

    public function fetchTransfers(ITransferFilter $filter=null): IList {
        core\stub();
    }

*/


// Transfer reversals
/*
    public function newTransferReversalCreateRequest(string $transferId): ITransferReversalCreateRequest {
        core\stub();
    }

    public function createTransferReversal(ITransferReversalCreateRequest $request): IData {
        core\stub();
    }

    public function fetchTransferReversal(string $transferId, string $reversalId): IData {
        core\stub();
    }


    public function newTransferReversalUpdateRequest(string $transferId, string $reversalId): ITransferReversalUpdateRequest {
        core\stub();
    }

    public function updateTransferReversal(ITransferReversalUpdateRequest $request): IData {
        core\stub();
    }


    public function newTransferReversalFilter(): ITransferReversalFilter {
        core\stub();
    }

    public function fetchTransferReversals(string $transferId, ITransferReversalFilter $filter=null): IList {
        core\stub();
    }

*/




### PAYMENT METHODS


// Alipay
    //--

// Bank accounts
    //--


// Cards
    public function createCard(string $customerId, mint\ICreditCard $card): IData {
        core\stub();
    }

    public function createCardFromToken(string $customerId, string $token): IData {
        core\stub();
    }

    public function fetchCard(string $customerId, string $cardId) {
        core\stub();
    }


    public function newCardUpdateRequest(string $customerId, string $cardId): ICardUpdateRequest {
        core\stub();
    }

    public function updateCard(ICardUpdateRequest $request): IData {
        core\stub();
    }

    public function replaceCard(string $customerId, string $cardId, mint\ICreditCard $card): IData {
        core\stub();
    }


    public function deleteCard(string $customerId, string $cardId) {
        core\stub();
    }


    public function newCardFilter(): ICardFilter {
        core\stub();
    }

    public function fetchCardList(string $customerId, ICardFilter $filter=null): IList {
        core\stub();
    }




// Sources
    //--




### RELAY
    //---






### SUBSCRIPTIONS

// Coupons
/*
    public function newCouponCreateRequest(string $id, string $type): ICouponCreateRequest {
        core\stub();
    }

    public function createCoupon(ICouponCreateRequest $request): IData {
        core\stub();
    }

    public function fetchCoupon(string $id): IData {
        core\stub();
    }


    public function newCouponUpdateRequest(string $id): ICouponUpdateRequest {
        core\stub();
    }

    public function updateCoupon(ICouponUpdateRequest $request): IData {
        core\stub();
    }


    public function deleteCoupon(string $id) {
        core\stub();
    }


    public function newCouponFilter(): ICouponFilter {
        core\stub();
    }

    public function fetchCoupons(ICouponFilter $filter=null): IList {
        core\stub();
    }

*/



// Discounts
/*
    public function deleteCustomerDiscount(string $customerId) {
        core\stub();
    }

    public function deleteSubscriptionDiscount(string $subscriptionId) {
        core\stub();
    }

*/



// Invoices
/*
    public function newInvoiceCreateRequest(string $customerId, string $description=null): IInvoiceCreateRequest {
        core\stub();
    }

    public function createInvoice(IInvoiceCreateRequest $request): IData {
        core\stub();
    }

    public function fetchInvoice(string $id): IData {
        core\stub();
    }


    public function newInvoiceLineFilter(): IInvoiceLineFilter {
        core\stub();
    }

    public function fetchInvoiceLines(string $invoiceId, IInvoiceLineFilter $filter=null): IList {
        core\stub();
    }


    public function newInvoicePreviewRequest(string $customerId): IInvoicePreviewRequest {
        core\stub();
    }

    public function previewInvoice(IInvoicePreviewRequest $request): IData {
        core\stub();
    }


    public function newInvoiceUpdateRequest(string $id): IInvoiceUpdateRequest {
        core\stub();
    }

    public function updateInvoice(IInvoiceUpdateRequest $request): IData {
        core\stub();
    }


    public function payInvoice(string $id): IData {
        core\stub();
    }


    public function newInvoiceFilter(string $customerId=null): IInvoiceFilter {
        core\stub();
    }

    public function fetchInvoices(IInvoiceFilter $filter=null): IList {
        core\stub();
    }

*/



// Invoice items
/*
    public function newInvoiceItemCreateRequest(string $customerId, mint\ICurrency $amount): IInvoiceItemCreateRequest {
        core\stub();
    }

    public function createInvoiceItem(IInvoiceItemCreateRequest $request): IData {
        core\stub();
    }

    public function fetchInvoiceItem(string $id): IData {
        core\stub();
    }


    public function newInvoiceItemUpdateRequest(string $id): IInvoiceItemUpdateRequest {
        core\stub();
    }

    public function updateInvoiceItem(IInvoiceItemUpdateRequest $request): IData {
        core\stub();
    }


    public function deleteInvoiceItem(string $id) {
        core\stub();
    }


    public function newInvoiceItemFilter(string $customerId=null): IInvoiceItemFilter {
        core\stub();
    }

    public function fetchInvoiceItems(IInvoiceItemFilter $filter=null): IList {
        core\stub();
    }

*/



// Plans
    public function newPlanCreateRequest(string $id, string $name, mint\ICurrency $amount, string $interval='month'): IPlanCreateRequest {
        core\stub();
    }

    public function createPlan(IPlanCreateRequest $request): IData {
        core\stub();
    }

    public function fetchPlan(string $id): IData {
        core\stub();
    }


    public function newPlanUpdateRequest(string $id): IPlanUpdateRequest {
        core\stub();
    }

    public function updatePlan(IPlanUpdateRequest $request): IData {
        core\stub();
    }


    public function deletePlan(string $id) {
        core\stub();
    }


    public function newPlanFilter(): IPlanFilter {
        core\stub();
    }

    public function fetchPlanList(IPlanFilter $filter=null): IList {
        core\stub();
    }




// Subscriptions
    public function newSubscriptionCreateRequest(string $customerId, string $planId=null): ISubscriptionCreateRequest {
        core\stub();
    }

    public function createSubscription(ISubscriptionCreateRequest $request): IData {
        core\stub();
    }

    public function fetchSubscription(string $id): IData {
        core\stub();
    }


    public function newSubscriptionUpdateRequest(string $id): ISubscriptionUpdateRequest {
        core\stub();
    }

    public function updateSubscription(ISubscriptionUpdateRequest $request): IData {
        core\stub();
    }


    public function cancelSubscription(string $id, bool $atPeriodEnd=false): IData {
        core\stub();
    }


    public function newSubscriptionFilter(string $customerId=null): ISubscriptionFilter {
        core\stub();
    }

    public function fetchSubscriptions(ISubscriptionFilter $filter=null) {
        core\stub();
    }




// Subscription items
/*
    public function newSubscriptionItemCreateRequest(string $subscriptionId, string $planId): ISubscriptionItemCreateRequest {
        core\stub();
    }

    public function createSubscriptionItem(ISubscriptionItemCreateRequest $request): IData {
        core\stub();
    }

    public function fetchSubscriptionId(string $id): IData {
        core\stub();
    }


    public function newSubscriptionItemUpdateRequest(string $id): ISubscriptionItemUpdateRequest {
        core\stub();
    }

    public function updateSubscriptionItem(ISubscriptionItemUpdateRequest $request): IData {
        core\stub();
    }


    public function newSubscriptionItemDeleteRequest(string $id): ISubscriptionItemDeleteRequest {
        core\stub();
    }

    public function deleteSubscriptionItem(ISubscriptionItemDeleteRequest $request): IData {
        core\stub();
    }


    public function newSubscriptionItemFilter(): ISubscriptionItemFilter {
        core\stub();
    }

    public function fetchSubscriptionItems(string $subscriptionId, ISubscriptionItemFilter $filter=null): IList {
        core\stub();
    }

*/




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
            'line1' => $this->_shippingAddress->getStreetLine1(),
            'line2' => $this->_shippingAddress->getStreetLine2(),
            'city' => $this->_shippingAddress->getLocality(),
            'state' => $this->_shippingAddress->getRegion(),
            'postal_code' => $this->_shippingAddress->getPostalCode(),
            'country' => $this->_shippingAddress->getCountryCode()
        ];
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