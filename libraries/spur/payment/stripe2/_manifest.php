<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\payment\stripe2;

use df;
use df\core;
use df\spur;
use df\mint;
use df\user;

interface IMediator extends spur\IHttpMediator {

// Api key
    public function setApiKey(string $key);
    public function getApiKey(): string;


// Account
    public function fetchAccountDetails(): IData;
    public function fetchCountrySpec(string $country): IData;


### CORE RESOURCES


// Balance
    public function fetchBalance(): IData;
    public function fetchBalanceTransaction(string $id): IData;

    public function newBalanceTransactionFilter(string $type=null): IBalanceTransactionFilter;
    public function fetchBalanceTransactions(IBalanceTransactionFilter $filter=null): IList;



// Charges
    public function newChargeCreateRequest(mint\ICurrency $amount, string $description=null): IChargeCreateRequest;
    public function createCharge(IChargeCreateRequest $request): IData;
    public function fetchCharge(string $id): IData;

    public function newChargeUpdateRequest(string $id): IChargeUpdateRequest;
    public function updateCharge(IChargeUpdateRequest $request): IData;

    public function newChargeFilter(string $customerId=null): IChargeFilter;
    public function fetchCharges(IChargeFilter $filter=null): IList;

    public function newChargeCaptureRequest(string $chargeId, mint\ICurrency $amount=null): IChargeCaptureRequest;
    public function captureCharge(IChargeCaptureRequest $request): IData;


// Customers
    public function newCustomerCreateRequest(string $emailAddress=null, string $description=null): ICustomerCreateRequest;
    public function createCustomer(ICustomerCreateRequest $request): IData;
    public function fetchCustomer(string $id): IData;

    public function newCustomerUpdateRequest(string $id): ICustomerUpdateRequest;
    public function updateCustomer(ICustomerUpdateRequest $request): IData;

    public function deleteCustomer(string $id);

    public function newCustomerFilter(): ICustomerFilter;
    public function fetchCustomers(ICustomerFilter $filter=null): IList;



// Disputes
/*
    public function fetchDispute(string $id): IData;

    public function newDisputeUpdateRequest(string $id): IDisputeUpdateRequest;
    public function updateDispute(IDisputeUpdateRequest $request): IData;

    public function closeDispute(string $id): IData;

    public function newDisputeFilter(): IDisputeFilter;
    public function fetchDisputes(IDisputeFilter $filter=null);
*/



// Events
/*
    public function fetchEvent(string $id): IData;

    public function newEventFilter(): IEventFilter;
    public function fetchEvents(IEventFilter $filter=null);
*/



// Files
/*
    public function uploadFile(core\fs\IFile $file, string $purpose): IData;
    public function fetchFileInfo(string $id): IData;

    public function newFileFilter(): IFileFilter;
    public function fetchFileInfos(IFileFilter $filter=null);
*/



// Refunds
/*
    public function newRefundCreateRequest(string $chargeId, string $reason=null): IRefundCreateRequest;
    public function createRefund(IRefundCreateRequest $request): IData;
    public function fetchRefund(string $id): IData;

    public function newRefundUpdateRequest(string $id): IRefundUpdateRequest;
    public function updateRefund(IRefundUpdateRequest $request);

    public function newRefundFilter(string $chargeId=null): IRefundFilter;
    public function fetchRefunds(IRefundFilter $filter=null): IList;
*/



// Tokens
/*
    public function createCardToken(mint\ICreditCard $card, string $customerId=null): IData;
    //public function createBankAccountToken(mint\IBankAccount $account, string $customerId=null): IData;
    //public function createPiiToken(string $id): IData;
    public function fetchToken(string $id): IData;
*/



// Transfers
/*
    public function newTransferCreateRequest(mint\ICurrency $amount, string $destinationId=null): ITransferCreateRequest;
    public function createTransfer(ITransferCreateRequest $request): IData;
    public function fetchTransfer(string $id): IData;

    public function newTransferUpdateRequest(string $id): ITransferUpdateRequest;
    public function updateTransfer(ITransferUpdateRequest $request): IData;

    public function newTransferFilter(string $recipient=null): ITransferFilter;
    public function fetchTransfers(ITransferFilter $filter=null): IList;
*/


// Transfer reversals
/*
    public function newTransferReversalCreateRequest(string $transferId): ITransferReversalCreateRequest;
    public function createTransferReversal(ITransferReversalCreateRequest $request): IData;
    public function fetchTransferReversal(string $transferId, string $reversalId): IData;

    public function newTransferReversalUpdateRequest(string $transferId, string $reversalId): ITransferReversalUpdateRequest;
    public function updateTransferReversal(ITransferReversalUpdateRequest $request): IData;

    public function newTransferReversalFilter(): ITransferReversalFilter;
    public function fetchTransferReversals(string $transferId, ITransferReversalFilter $filter=null): IList;
*/




### PAYMENT METHODS


// Alipay
    //--

// Bank accounts
    //--


// Cards
    public function createCard(string $customerId, mint\ICreditCard $card): IData;
    public function createCardFromToken(string $customerId, string $token): IData;
    public function fetchCard(string $customerId, string $cardId);

    public function newCardUpdateRequest(string $customerId, string $cardId): ICardUpdateRequest;
    public function updateCard(ICardUpdateRequest $request): IData;
    public function replaceCard(string $customerId, string $cardId, mint\ICreditCard $card): IData;

    public function deleteCard(string $customerId, string $cardId);

    public function newCardFilter(): ICardFilter;
    public function fetchCardList(string $customerId, ICardFilter $filter=null): IList;



// Sources
    //--




### RELAY
    //---






### SUBSCRIPTIONS

// Coupons
/*
    public function newCouponCreateRequest(string $id, string $type): ICouponCreateRequest;
    public function createCoupon(ICouponCreateRequest $request): IData;
    public function fetchCoupon(string $id): IData;

    public function newCouponUpdateRequest(string $id): ICouponUpdateRequest;
    public function updateCoupon(ICouponUpdateRequest $request): IData;

    public function deleteCoupon(string $id);

    public function newCouponFilter(): ICouponFilter;
    public function fetchCoupons(ICouponFilter $filter=null): IList;
*/



// Discounts
/*
    public function deleteCustomerDiscount(string $customerId);
    public function deleteSubscriptionDiscount(string $subscriptionId);
*/



// Invoices
/*
    public function newInvoiceCreateRequest(string $customerId, string $description=null): IInvoiceCreateRequest;
    public function createInvoice(IInvoiceCreateRequest $request): IData;
    public function fetchInvoice(string $id): IData;

    public function newInvoiceLineFilter(): IInvoiceLineFilter;
    public function fetchInvoiceLines(string $invoiceId, IInvoiceLineFilter $filter=null): IList;

    public function newInvoicePreviewRequest(string $customerId): IInvoicePreviewRequest;
    public function previewInvoice(IInvoicePreviewRequest $request): IData;

    public function newInvoiceUpdateRequest(string $id): IInvoiceUpdateRequest;
    public function updateInvoice(IInvoiceUpdateRequest $request): IData;

    public function payInvoice(string $id): IData;

    public function newInvoiceFilter(string $customerId=null): IInvoiceFilter;
    public function fetchInvoices(IInvoiceFilter $filter=null): IList;
*/



// Invoice items
/*
    public function newInvoiceItemCreateRequest(string $customerId, mint\ICurrency $amount): IInvoiceItemCreateRequest;
    public function createInvoiceItem(IInvoiceItemCreateRequest $request): IData;
    public function fetchInvoiceItem(string $id): IData;

    public function newInvoiceItemUpdateRequest(string $id): IInvoiceItemUpdateRequest;
    public function updateInvoiceItem(IInvoiceItemUpdateRequest $request): IData;

    public function deleteInvoiceItem(string $id);

    public function newInvoiceItemFilter(string $customerId=null): IInvoiceItemFilter;
    public function fetchInvoiceItems(IInvoiceItemFilter $filter=null): IList;
*/



// Plans
    public function newPlanCreateRequest(string $id, string $name, mint\ICurrency $amount, string $interval='month'): IPlanCreateRequest;
    public function createPlan(IPlanCreateRequest $request): IData;
    public function fetchPlan(string $id): IData;

    public function newPlanUpdateRequest(string $id): IPlanUpdateRequest;
    public function updatePlan(IPlanUpdateRequest $request): IData;

    public function deletePlan(string $id);

    public function newPlanFilter(): IPlanFilter;
    public function fetchPlanList(IPlanFilter $filter=null): IList;



// Subscriptions
    public function newSubscriptionCreateRequest(string $customerId, string $planId=null): ISubscriptionCreateRequest;
    public function createSubscription(ISubscriptionCreateRequest $request): IData;
    public function fetchSubscription(string $id): IData;

    public function newSubscriptionUpdateRequest(string $id): ISubscriptionUpdateRequest;
    public function updateSubscription(ISubscriptionUpdateRequest $request): IData;

    public function cancelSubscription(string $id, bool $atPeriodEnd=false): IData;

    public function newSubscriptionFilter(string $customerId=null): ISubscriptionFilter;
    public function fetchSubscriptions(ISubscriptionFilter $filter=null);



// Subscription items
/*
    public function newSubscriptionItemCreateRequest(string $subscriptionId, string $planId): ISubscriptionItemCreateRequest;
    public function createSubscriptionItem(ISubscriptionItemCreateRequest $request): IData;
    public function fetchSubscriptionId(string $id): IData;

    public function newSubscriptionItemUpdateRequest(string $id): ISubscriptionItemUpdateRequest;
    public function updateSubscriptionItem(ISubscriptionItemUpdateRequest $request): IData;

    public function newSubscriptionItemDeleteRequest(string $id): ISubscriptionItemDeleteRequest;
    public function deleteSubscriptionItem(ISubscriptionItemDeleteRequest $request): IData;

    public function newSubscriptionItemFilter(): ISubscriptionItemFilter;
    public function fetchSubscriptionItems(string $subscriptionId, ISubscriptionItemFilter $filter=null): IList;
*/
}




// Data
interface IData extends core\collection\ITree {
    public function setType(string $type);
    public function getType(): string;

    public function setRequest(IRequest $request);
    public function getRequest()/*: ?IRequest*/;
}


// List
interface IList extends core\IArrayProvider, \IteratorAggregate {
    public function getTotal(): int;
    public function hasMore(): bool;

    public function getStartingAfter()/*: ?string*/;
    public function getEndingBefore()/*: ?string*/;

    public function setFilter(IFilter $filter);
    public function getFilter(): IFilter;
}


// Request
interface IRequest extends core\IArrayProvider {

}

interface ICreateRequest extends IRequest {}
interface IUpdateRequest extends IRequest {}
interface IDeleteRequest extends IRequest {}


interface IApplicationFeeSubRequest extends IRequest {
    public function setApplicationFee(/*?mint\ICurrency*/ $fee);
    public function getApplicationFee()/*: ?mint\ICurrency*/;
}

interface IChargeIdSubRequest extends IRequest {
    public function setChargeId(string $id);
    public function getChargeId(): string;
}

interface IDescriptionSubRequest extends IRequest {
    public function setDescription(/*?string */ $description);
    public function getDescription()/*: ?string*/;
}

interface IMetadataSubRequest extends IRequest {
    public function setMetadata(/*?array */ $metadata);
    public function getMetadata()/*: ?array*/;
}

interface IReceiptEmailSubRequest extends IRequest {
    public function setReceiptEmail(/*?string*/ $email);
    public function getReceiptEmail()/*: ?string*/;
}

interface IShippingSubRequest extends IRequest {
    public function setShippingAddress(/*?user\IPostalAddress*/ $address);
    public function getShippingAddress()/*: ?user\IPostralAddress*/;

    public function setCarrier(/*?string*/ $carrier);
    public function getCarrier()/*: ?string*/;

    public function setRecipientName(/*?string*/ $name);
    public function getRecipientName()/*: ?string*/;

    public function setRecipientPhone(/*?string*/ $phone);
    public function getRecipientPhone()/*: ?string*/;

    public function setTrackingNumber(/*?string*/ $number);
    public function getTrackingNumber()/*: ?string*/;
}

interface IStatementDescriptorSubRequest extends IRequest {
    public function setStatementDescriptor(/*?string*/ $descriptor);
    public function getStatementDescriptor()/*: ?string*/;
}

interface ITransferGroupSubRequest extends IRequest {
    public function setTransferGroup(/*?string*/ $group);
    public function getTransferGroup()/*: ?string*/;
}



// Filter
interface IFilter extends core\IArrayProvider {
    public function setLimit(int $limit);
    public function getLimit(): int;

    public function setStartingAfter(/*?string*/ $id);
    public function getStartingAfter()/*: ?string*/;

    public function setEndingBefore(/*?string*/ $id);
    public function getEndingBefore()/*: ?string*/;
}


interface IAvailabilitySubFilter extends IFilter {
    public function whereAvailableOn(/*?array*/ $availability);
    public function getAvailability()/*: ?array*/;
}

interface ICreatedSubFilter extends IFilter {
    public function whereCreated(/*?array*/ $created);
    public function getCreated()/*: ?array*/;
}

interface ICurrencySubFilter extends IFilter {
    public function setCurrency(/*?string*/ $currency);
    public function getCurrency()/*: ?string*/;
}

interface ICustomerSubFilter extends IFilter {
    public function setCustomerId(/*?string*/ $customerId);
    public function getCustomerId()/*: ?string*/;
}

interface ISourceSubFilter extends IFilter {
    public function setSource(string $source);
    public function getSource(): string;
}


### TYPES


// Balance
interface IBalanceTransactionFilter extends IFilter,
    IAvailabilitySubFilter, ICreatedSubFilter, ICurrencySubFilter {

    public function isSourceOnly(bool $flag=null);

    public function setTransferId(/*?string*/ $transferId);
    public function getTransferId()/*: ?string*/;

    public function setType(/*?string*/ $type);
    public function getType()/*: ?string*/;
}


// Charges
interface IChargeRequest extends IRequest {}

interface IChargeCreateRequest extends IChargeRequest, ICreateRequest,
    IDescriptionSubRequest, IApplicationFeeSubRequest, IReceiptEmailSubRequest,
    ITransferGroupSubRequest, IMetadataSubRequest, IShippingSubRequest, IStatementDescriptorSubRequest {
    public function setAmount(mint\ICurrency $amount);
    public function getAmount(): mint\ICurrency;

    public function shouldCapture(bool $flag=null);

    public function setDestination(/*?string*/ $accountId, mint\ICurrency $amount=null);
    public function getDestinationAccountId()/*?string*/;
    public function getDestinationAmount()/*?mint\ICurrency*/;

    public function setOnBehalfOfAccountId(/*?string*/ $accountId);
    public function getOnBehalfOfAccountId()/*: ?string*/;

    public function setCustomerId(/*?string*/ $customerId);
    public function getCustomerId()/*: ?string*/;

    public function setCard(/*?mint\ICreditCard*/ $card);
    public function setSourceId(/*?string*/ $source);
    public function getSource();
}

interface IChargeUpdateRequest extends IChargeRequest, IUpdateRequest,
    IDescriptionSubRequest, IChargeIdSubRequest, ITransferGroupSubRequest,
    IMetadataSubRequest, IShippingSubRequest {
    public function setFraudDetails(/*?array*/ $details);
    public function getFraudDetails()/*: ?array*/;
}

interface IChargeCaptureRequest extends IChargeRequest,
    IChargeIdSubRequest, IApplicationFeeSubRequest, IReceiptEmailSubRequest, IStatementDescriptorSubRequest {
    public function setAmount(/*?mint\ICurrency*/ $amount);
    public function getAmount()/*: ?mint\ICurrency*/;
}

interface IChargeFilter extends IFilter,
    ICreatedSubFilter, ICustomerSubFilter, ISourceSubFilter {
    public function setTransferGroup(/*?string*/ $group);
    public function getTransferGroup()/*: ?string*/;
}



// Customers
interface ICustomerRequest extends IRequest {

}

interface ICustomerCreateRequest extends ICustomerRequest, ICreateRequest {

}

interface ICustomerUpdateRequest extends ICustomerRequest, IUpdateRequest {

}

interface ICustomerFilter extends IFilter {

}



// Disputes
interface IDisputeRequest extends IRequest {

}

interface IDisputeUpdateRequest extends IDisputeRequest, IUpdateRequest {

}

interface IDisputeFilter extends IFilter {

}



// Events
interface IEventFilter extends IFilter {

}



// Filters
interface IFileFilter extends IFilter {

}



// Refunds
interface IRefundRequest extends IRequest {

}

interface IRefundCreateRequest extends IRefundRequest, ICreateRequest {

}

interface IRefundUpdateRequest extends IRefundRequest, IUpdateRequest {

}

interface IRefundFilter extends IFilter {

}



// Transfers
interface ITransferRequest extends IRequest {

}

interface ITransferCreateRequest extends ITransferRequest, ICreateRequest {

}

interface ITransferUpdateRequest extends ITransferRequest, IUpdateRequest {

}

interface ITransferFilter extends IFilter {

}



// Transfer reversals
interface ITransferReversalRequest extends IRequest {

}

interface ITransferReversalCreateRequest extends ITransferReversalRequest, ICreateRequest {

}

interface ITransferReversalUpdateRequest extends ITransferReversalRequest, IUpdateRequest {

}

interface ITransferReversalFilter extends IFilter {

}


// Alipay
    //--

// Bank accounts
    //--


// Cards
interface ICardRequest extends IRequest {

}

interface ICardUpdateRequest extends ICardRequest, IUpdateRequest {

}

interface ICardFilter extends IFilter {

}


// Sources
    //--



// Coupons
interface ICouponRequest extends IRequest {

}

interface ICouponCreateRequest extends ICouponRequest, ICreateRequest {

}

interface ICouponUpdateRequest extends ICouponRequest, IUpdateRequest {

}

interface ICouponFilter extends IFilter {

}



// Invoices
interface IInvoiceRequest extends IRequest {

}

interface IInvoiceCreateRequest extends IInvoiceRequest, ICreateRequest {

}

interface IInvoiceUpdateRequest extends IInvoiceRequest, IUpdateRequest {

}

interface IInvoicePreviewRequest extends IInvoiceRequest {

}

interface IInvoiceFilter extends IFilter {

}

interface IInvoiceLineFilter extends IFilter {

}



// Invoice items
interface IInvoiceItemRequest extends IRequest {

}

interface IInvoiceItemCreateRequest extends IInvoiceItemRequest, ICreateRequest {

}

interface IInvoiceItemUpdateRequest extends IInvoiceItemRequest, IUpdateRequest {

}

interface IInvoiceItemFilter extends IFilter {

}



// Plans
interface IPlanRequest extends IRequest {

}

interface IPlanCreateRequest extends IPlanRequest, ICreateRequest {

}

interface IPlanUpdateRequest extends IPlanRequest, IUpdateRequest {

}

interface IPlanFilter extends IFilter {

}



// Subscriptions
interface ISubscriptionRequest extends IRequest {

}

interface ISubscriptionCreateRequest extends ISubscriptionRequest, ICreateRequest {

}

interface ISubscriptionUpdateRequest extends ISubscriptionRequest, IUpdateRequest {

}

interface ISubscriptionFilter extends IFilter {

}



// Subscription items
interface ISubscriptionItemRequest extends IRequest {

}

interface ISubscriptionItemCreateRequest extends ISubscriptionRequest, ICreateRequest {

}

interface ISubscriptionItemUpdateRequest extends ISubscriptionRequest, IUpdateRequest {

}

interface ISubscriptionItemDeleteRequest extends ISubscriptionRequest, IDeleteRequest {

}

interface ISubscriptionItemFilter extends IFilter {

}
