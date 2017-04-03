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
    public function fetchAccountDetails(): IDataObject;
    public function fetchCountrySpec(string $country): IDataObject;


// IPs
    public function fetchApiIps(): array;
    public function fetchWebhookIps(): array;


### CORE RESOURCES


// Balance
    public function fetchBalance(): IDataObject;
    public function fetchBalanceTransaction(string $id): IDataObject;

    public function newBalanceTransactionFilter(string $type=null): IBalanceTransactionFilter;
    public function fetchBalanceTransactions(IBalanceTransactionFilter $filter=null): IDataList;



// Charges
    public function newChargeCreateRequest(mint\ICurrency $amount, string $description=null): IChargeCreateRequest;
    public function createCharge(IChargeCreateRequest $request): IDataObject;
    public function fetchCharge(string $id): IDataObject;

    public function newChargeUpdateRequest(string $id): IChargeUpdateRequest;
    public function updateCharge(IChargeUpdateRequest $request): IDataObject;

    public function newChargeFilter(string $customerId=null): IChargeFilter;
    public function fetchCharges(IChargeFilter $filter=null): IDataList;

    public function newChargeCaptureRequest(string $chargeId, mint\ICurrency $amount=null): IChargeCaptureRequest;
    public function captureCharge(IChargeCaptureRequest $request): IDataObject;


// Customers
    public function newCustomerCreateRequest(string $emailAddress=null, string $description=null): ICustomerCreateRequest;
    public function createCustomer(ICustomerCreateRequest $request): IDataObject;
    public function fetchCustomer(string $id): IDataObject;

    public function newCustomerUpdateRequest(string $id): ICustomerUpdateRequest;
    public function updateCustomer(ICustomerUpdateRequest $request): IDataObject;

    public function deleteCustomer(string $id);

    public function newCustomerFilter(): ICustomerFilter;
    public function fetchCustomers(ICustomerFilter $filter=null): IDataList;



// Disputes
/*
    public function fetchDispute(string $id): IDataObject;

    public function newDisputeUpdateRequest(string $id): IDisputeUpdateRequest;
    public function updateDispute(IDisputeUpdateRequest $request): IDataObject;

    public function closeDispute(string $id): IDataObject;

    public function newDisputeFilter(): IDisputeFilter;
    public function fetchDisputes(IDisputeFilter $filter=null);
*/



// Events
/*
    public function fetchEvent(string $id): IDataObject;

    public function newEventFilter(): IEventFilter;
    public function fetchEvents(IEventFilter $filter=null);
*/



// Files
/*
    public function uploadFile(core\fs\IFile $file, string $purpose): IDataObject;
    public function fetchFileInfo(string $id): IDataObject;

    public function newFileFilter(): IFileFilter;
    public function fetchFileInfos(IFileFilter $filter=null);
*/



// Refunds
    public function newRefundCreateRequest(string $chargeId, string $reason=null): IRefundCreateRequest;
    public function createRefund(IRefundCreateRequest $request): IDataObject;
    public function fetchRefund(string $id): IDataObject;

    public function newRefundUpdateRequest(string $id): IRefundUpdateRequest;
    public function updateRefund(IRefundUpdateRequest $request);

    public function newRefundFilter(string $chargeId=null): IRefundFilter;
    public function fetchRefunds(IRefundFilter $filter=null): IDataList;



// Tokens
/*
    public function createCardToken(mint\ICreditCard $card, string $customerId=null): IDataObject;
    //public function createBankAccountToken(mint\IBankAccount $account, string $customerId=null): IDataObject;
    //public function createPiiToken(string $id): IDataObject;
    public function fetchToken(string $id): IDataObject;
*/



// Transfers
/*
    public function newTransferCreateRequest(mint\ICurrency $amount, string $destinationId=null): ITransferCreateRequest;
    public function createTransfer(ITransferCreateRequest $request): IDataObject;
    public function fetchTransfer(string $id): IDataObject;

    public function newTransferUpdateRequest(string $id): ITransferUpdateRequest;
    public function updateTransfer(ITransferUpdateRequest $request): IDataObject;

    public function newTransferFilter(string $recipient=null): ITransferFilter;
    public function fetchTransfers(ITransferFilter $filter=null): IDataList;
*/


// Transfer reversals
/*
    public function newTransferReversalCreateRequest(string $transferId): ITransferReversalCreateRequest;
    public function createTransferReversal(ITransferReversalCreateRequest $request): IDataObject;
    public function fetchTransferReversal(string $transferId, string $reversalId): IDataObject;

    public function newTransferReversalUpdateRequest(string $transferId, string $reversalId): ITransferReversalUpdateRequest;
    public function updateTransferReversal(ITransferReversalUpdateRequest $request): IDataObject;

    public function newTransferReversalFilter(): ITransferReversalFilter;
    public function fetchTransferReversals(string $transferId, ITransferReversalFilter $filter=null): IDataList;
*/




### PAYMENT METHODS


// Alipay
    //--

// Bank accounts
    //--


// Cards
    public function newCardCreateRequest(string $customerId, $source): ICardCreateRequest;
    public function createCard(ICardCreateRequest $request): IDataObject;
    public function fetchCard(string $customerId, string $cardId);

    public function newCardUpdateRequest(string $customerId, string $cardId): ICardUpdateRequest;
    public function updateCard(ICardUpdateRequest $request): IDataObject;

    public function deleteCard(string $customerId, string $cardId);

    public function newCardFilter(): ICardFilter;
    public function fetchCards(string $customerId, ICardFilter $filter=null): IDataList;



// Sources
    //--




### RELAY
    //---






### SUBSCRIPTIONS

// Coupons
/*
    public function newCouponCreateRequest(string $id, string $type): ICouponCreateRequest;
    public function createCoupon(ICouponCreateRequest $request): IDataObject;
    public function fetchCoupon(string $id): IDataObject;

    public function newCouponUpdateRequest(string $id): ICouponUpdateRequest;
    public function updateCoupon(ICouponUpdateRequest $request): IDataObject;

    public function deleteCoupon(string $id);

    public function newCouponFilter(): ICouponFilter;
    public function fetchCoupons(ICouponFilter $filter=null): IDataList;
*/



// Discounts
/*
    public function deleteCustomerDiscount(string $customerId);
    public function deleteSubscriptionDiscount(string $subscriptionId);
*/



// Invoices
/*
    public function newInvoiceCreateRequest(string $customerId, string $description=null): IInvoiceCreateRequest;
    public function createInvoice(IInvoiceCreateRequest $request): IDataObject;
    public function fetchInvoice(string $id): IDataObject;

    public function newInvoiceLineFilter(): IInvoiceLineFilter;
    public function fetchInvoiceLines(string $invoiceId, IInvoiceLineFilter $filter=null): IDataList;

    public function newInvoicePreviewRequest(string $customerId): IInvoicePreviewRequest;
    public function previewInvoice(IInvoicePreviewRequest $request): IDataObject;

    public function newInvoiceUpdateRequest(string $id): IInvoiceUpdateRequest;
    public function updateInvoice(IInvoiceUpdateRequest $request): IDataObject;

    public function payInvoice(string $id): IDataObject;

    public function newInvoiceFilter(string $customerId=null): IInvoiceFilter;
    public function fetchInvoices(IInvoiceFilter $filter=null): IDataList;
*/



// Invoice items
/*
    public function newInvoiceItemCreateRequest(string $customerId, mint\ICurrency $amount): IInvoiceItemCreateRequest;
    public function createInvoiceItem(IInvoiceItemCreateRequest $request): IDataObject;
    public function fetchInvoiceItem(string $id): IDataObject;

    public function newInvoiceItemUpdateRequest(string $id): IInvoiceItemUpdateRequest;
    public function updateInvoiceItem(IInvoiceItemUpdateRequest $request): IDataObject;

    public function deleteInvoiceItem(string $id);

    public function newInvoiceItemFilter(string $customerId=null): IInvoiceItemFilter;
    public function fetchInvoiceItems(IInvoiceItemFilter $filter=null): IDataList;
*/



// Plans
    public function newPlanCreateRequest(string $id, string $name, mint\ICurrency $amount, string $interval='month'): IPlanCreateRequest;
    public function createPlan(IPlanCreateRequest $request): IDataObject;
    public function fetchPlan(string $id): IDataObject;

    public function newPlanUpdateRequest(string $id): IPlanUpdateRequest;
    public function updatePlan(IPlanUpdateRequest $request): IDataObject;

    public function deletePlan(string $id);

    public function newPlanFilter(): IPlanFilter;
    public function fetchPlans(IPlanFilter $filter=null): IDataList;



// Subscriptions
    public function newSubscriptionCreateRequest(string $customerId, string $planId=null): ISubscriptionCreateRequest;
    public function createSubscription(ISubscriptionCreateRequest $request): IDataObject;
    public function fetchSubscription(string $id): IDataObject;

    public function newSubscriptionUpdateRequest(string $id): ISubscriptionUpdateRequest;
    public function updateSubscription(ISubscriptionUpdateRequest $request): IDataObject;

    public function cancelSubscription(string $id, bool $atPeriodEnd=false): IDataObject;

    public function newSubscriptionFilter(string $customerId=null): ISubscriptionFilter;
    public function fetchSubscriptions(ISubscriptionFilter $filter=null);



// Subscription items
/*
    public function newSubscriptionItemCreateRequest(string $subscriptionId, string $planId): ISubscriptionItemCreateRequest;
    public function createSubscriptionItem(ISubscriptionItemCreateRequest $request): IDataObject;
    public function fetchSubscriptionId(string $id): IDataObject;

    public function newSubscriptionItemUpdateRequest(string $id): ISubscriptionItemUpdateRequest;
    public function updateSubscriptionItem(ISubscriptionItemUpdateRequest $request): IDataObject;

    public function newSubscriptionItemDeleteRequest(string $id): ISubscriptionItemDeleteRequest;
    public function deleteSubscriptionItem(ISubscriptionItemDeleteRequest $request): IDataObject;

    public function newSubscriptionItemFilter(): ISubscriptionItemFilter;
    public function fetchSubscriptionItems(string $subscriptionId, ISubscriptionItemFilter $filter=null): IDataList;
*/
}




// DataObject
interface IDataObject extends core\collection\ITree {
    public function setType(string $type);
    public function getType(): string;

    public function setRequest(IRequest $request);
    public function getRequest(): ?IRequest;
}


// List
interface IDataList extends core\IArrayProvider, \IteratorAggregate {
    public function getTotal(): int;
    public function hasMore(): bool;

    public function getStartingAfter(): ?string;
    public function getEndingBefore(): ?string;

    public function setFilter(IFilter $filter);
    public function getFilter(): IFilter;
}


// Request
interface IRequest extends core\IArrayProvider {}

interface IApplicationFeeSubRequest extends IRequest {
    public function setApplicationFee(?mint\ICurrency $fee);
    public function getApplicationFee(): ?mint\ICurrency;
}

interface IApplicationFeePercentSubRequest extends IRequest {
    public function setApplicationFeePercent(?float $percent);
    public function getApplicationFeePercent(): ?float;
}

interface IChargeIdSubRequest extends IRequest {
    public function setChargeId(string $id);
    public function getChargeId(): string;
}

interface ICouponSubRequest extends IRequest {
    public function setCouponId(?string $id);
    public function getCouponId(): ?string;
}

interface IDescriptionSubRequest extends IRequest {
    public function setDescription(?string $description);
    public function getDescription(): ?string;
}

interface IEmailSubRequest extends IRequest {
    public function setEmailAddress(?string $email);
    public function getEmailAddress(): ?string;
}

interface IMetadataSubRequest extends IRequest {
    public function setMetadata(?array $metadata);
    public function getMetadata(): ?array;
}

interface IPlanSubRequest extends IRequest {
    public function setPlanId(string $id);
    public function getPlanId(): string;
}

interface IProrateSubRequest extends IRequest {
    public function setProrate(?bool $prorate);
    public function getProrate(): ?bool;
}

interface IShippingSubRequest extends IRequest {
    public function setShippingAddress(?user\IPostalAddress $address);
    public function getShippingAddress(): ?user\IPostralAddress;

    public function setRecipientName(?string $name);
    public function getRecipientName(): ?string;

    public function setRecipientPhone(?string $phone);
    public function getRecipientPhone(): ?string;
}

interface IShippedSubRequest extends IShippingSubRequest {
    public function setCarrier(?string $carrier);
    public function getCarrier(): ?string;

    public function setTrackingNumber(?string $number);
    public function getTrackingNumber(): ?string;
}

interface ISourceSubRequest extends IRequest {
    public function setCard(?mint\ICreditCard $card);
    public function setSourceId(?string $source);
    public function setSource($source);
    public function getSource();
}

interface IStatementDescriptorSubRequest extends IRequest {
    public function setStatementDescriptor(?string $descriptor);
    public function getStatementDescriptor(): ?string;
}

interface ITransferGroupSubRequest extends IRequest {
    public function setTransferGroup(?string $group);
    public function getTransferGroup(): ?string;
}

interface ITrialDaysSubRequest extends IRequest {
    public function setTrialDays(?int $days);
    public function getTrialDays(): ?int;
}

interface ITrialEndSubRequest extends IRequest {
    public function setTrialEnd($date);
    public function getTrialEnd(): ?core\time\IDate;
}



// Filter
interface IFilter extends core\IArrayProvider {
    public function setLimit(int $limit);
    public function getLimit(): int;

    public function setStartingAfter(?string $id);
    public function getStartingAfter(): ?string;

    public function setEndingBefore(?string $id);
    public function getEndingBefore(): ?string;

    public function hasPointer(): bool;
}


interface IAvailabilitySubFilter extends IFilter {
    public function whereAvailableOn(?array $availability);
    public function getAvailability(): ?array;
}

interface ICreatedSubFilter extends IFilter {
    public function whereCreated(?array $created);
    public function getCreated(): ?array;
}

interface ICurrencySubFilter extends IFilter {
    public function setCurrency(?string $currency);
    public function getCurrency(): ?string;
}

interface ICustomerSubFilter extends IFilter {
    public function setCustomerId(?string $customerId);
    public function getCustomerId(): ?string;
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

    public function setTransferId(?string $transferId);
    public function getTransferId(): ?string;

    public function setType(?string $type);
    public function getType(): ?string;
}


// Charges
interface IChargeRequest extends IRequest {}

interface IChargeCreateRequest extends IChargeRequest,
    IDescriptionSubRequest, IApplicationFeeSubRequest, IEmailSubRequest,
    ITransferGroupSubRequest, IMetadataSubRequest, IShippedSubRequest,
    IStatementDescriptorSubRequest, ISourceSubRequest {
    public function setAmount(mint\ICurrency $amount);
    public function getAmount(): mint\ICurrency;

    public function shouldCapture(bool $flag=null);

    public function setDestination(?string $accountId, mint\ICurrency $amount=null);
    public function getDestinationAccountId(): ?string;
    public function getDestinationAmount(): ?mint\ICurrency;

    public function setOnBehalfOfAccountId(?string $accountId);
    public function getOnBehalfOfAccountId(): ?string;

    public function setCustomerId(?string $customerId);
    public function getCustomerId(): ?string;
}

interface IChargeUpdateRequest extends IChargeRequest,
    IDescriptionSubRequest, IChargeIdSubRequest, ITransferGroupSubRequest,
    IMetadataSubRequest, IShippedSubRequest {
    public function setFraudDetails(?array $details);
    public function getFraudDetails(): ?array;
}

interface IChargeCaptureRequest extends IChargeRequest,
    IChargeIdSubRequest, IApplicationFeeSubRequest, IEmailSubRequest,
    IStatementDescriptorSubRequest {
    public function setAmount(?mint\ICurrency $amount);
    public function getAmount(): ?mint\ICurrency;
}

interface IChargeFilter extends IFilter,
    ICreatedSubFilter, ICustomerSubFilter, ISourceSubFilter {
    public function setTransferGroup(?string $group);
    public function getTransferGroup(): ?string;
}



// Customers
interface ICustomerRequest extends IRequest,
    IDescriptionSubRequest, IEmailSubRequest, IMetadataSubRequest,
    IShippingSubRequest, ISourceSubRequest {
    public function setBalance(?mint\ICurrency $balance);
    public function getBalance(): ?mint\ICurrency;

    public function setVatId(?string $id);
    public function getVatId(): ?string;

    public function setCouponId(?string $id);
    public function getCouponId(): ?string;
}

interface ICustomerCreateRequest extends ICustomerRequest {}

interface ICustomerUpdateRequest extends ICustomerRequest {
    public function setCustomerId(string $id);
    public function getCustomerId(): string;
}

interface ICustomerFilter extends IFilter, ICreatedSubFilter {}



// Disputes
interface IDisputeRequest extends IRequest {}
interface IDisputeUpdateRequest extends IDisputeRequest {}
interface IDisputeFilter extends IFilter {}



// Events
interface IEventFilter extends IFilter {}



// Filters
interface IFileFilter extends IFilter {}



// Refunds
interface IRefundRequest extends IRequest, IMetadataSubRequest {}

interface IRefundCreateRequest extends IRefundRequest, IChargeIdSubRequest {
    public function setAmount(?mint\ICurrency $amount);
    public function getAmount(): ?mint\ICurrency;

    public function setReason(?string $reason);
    public function getReason(): ?string;

    public function shouldIncludeApplicationFee(bool $flag=null);
    public function shouldReverseTransfer(bool $flag=null);
}

interface IRefundUpdateRequest extends IRefundRequest {
    public function setRefundId(string $id);
    public function getRefundId(): string;
}

interface IRefundFilter extends IFilter {
    public function setChargeId(?string $chargeId);
    public function getChargeId(): ?string;
}



// Transfers
interface ITransferRequest extends IRequest {}
interface ITransferCreateRequest extends ITransferRequest {}
interface ITransferUpdateRequest extends ITransferRequest {}
interface ITransferFilter extends IFilter {}



// Transfer reversals
interface ITransferReversalRequest extends IRequest {}
interface ITransferReversalCreateRequest extends ITransferReversalRequest {}
interface ITransferReversalUpdateRequest extends ITransferReversalRequest {}
interface ITransferReversalFilter extends IFilter {}


// Alipay
    //--

// Bank accounts
    //--


// Cards
interface ICardRequest extends IRequest, IMetadataSubRequest {
    public function setCustomerId(string $customerId);
    public function getCustomerId(): string;
}

interface ICardCreateRequest extends ICardRequest, ISourceSubRequest {}

interface ICardUpdateRequest extends ICardRequest {
    public function setCardId(string $cardId);
    public function getCardId(): string;

    public function setStreetLine1(?string $line1);
    public function getStreetLine1(): ?string;
    public function setStreetLine2(?string $line2);
    public function getStreetLine2(): ?string;
    public function setLocality(?string $locality);
    public function getLocality(): ?string;
    public function setRegion(?string $region);
    public function getRegion(): ?string;
    public function setPostalCode(?string $code);
    public function getPostalCode(): ?string;
    public function setCountry(?string $country);
    public function getCountry(): ?string;

    public function setExpiryMonth(?int $month);
    public function getExpiryMonth(): ?int;
    public function setExpiryYear(?int $year);
    public function getExpiryYear(): ?int;

    public function setName(?string $name);
    public function getName(): ?string;
}

interface ICardFilter extends IFilter {}


// Sources
    //--



// Coupons
interface ICouponRequest extends IRequest {}
interface ICouponCreateRequest extends ICouponRequest {}
interface ICouponUpdateRequest extends ICouponRequest {}
interface ICouponFilter extends IFilter {}



// Invoices
interface IInvoiceRequest extends IRequest {}
interface IInvoiceCreateRequest extends IInvoiceRequest {}
interface IInvoiceUpdateRequest extends IInvoiceRequest {}
interface IInvoicePreviewRequest extends IInvoiceRequest {}
interface IInvoiceFilter extends IFilter {}
interface IInvoiceLineFilter extends IFilter {}



// Invoice items
interface IInvoiceItemRequest extends IRequest {}
interface IInvoiceItemCreateRequest extends IInvoiceItemRequest {}
interface IInvoiceItemUpdateRequest extends IInvoiceItemRequest {}
interface IInvoiceItemFilter extends IFilter {}



// Plans
interface IPlanRequest extends IRequest,
    IPlanSubRequest, IMetadataSubRequest, IStatementDescriptorSubRequest,
    ITrialDaysSubRequest {}

interface IPlanCreateRequest extends IPlanRequest {
    public function setAmount(mint\ICurrency $amount);
    public function getAmount(): mint\ICurrency;

    public function setName(string $name);
    public function getName(): string;

    public function setInterval(string $interval, int $count=null);
    public function getInterval(): string;

    public function setIntervalCount(int $count);
    public function getIntervalCount(): int;
}

interface IPlanUpdateRequest extends IPlanRequest {
    public function setName(?string $name);
    public function getName(): ?string;
}

interface IPlanFilter extends IFilter, ICreatedSubFilter {}



// Subscriptions
interface ISubscriptionRequest extends IRequest,
    IApplicationFeePercentSubRequest, ICouponSubRequest, IProrateSubRequest,
    ISourceSubRequest, ITrialEndSubRequest {
    public function setPlan(string $planId, int $quantity=1);
    public function getPlan(): ?ISubscriptionItem;
    public function clearPlan();

    public function addPlan(string $planId, int $quantity=1);
    public function newItem(string $planId, int $quantity=1): ISubscriptionItem;

    public function setItems(array $items);
    public function addItems(array $items);
    public function addItem(ISubscriptionItem $item);
    public function deleteItem(string $itemId);
    public function clearItems();
    public function getItems(): array;

    public function setTaxPercent(?float $percent);
    public function getTaxPercent(): ?float;
}

interface ISubscriptionCreateRequest extends ISubscriptionRequest, ITrialDaysSubRequest {
    public function setCustomerId(string $customerId);
    public function getCustomerId(): string;
}

interface ISubscriptionUpdateRequest extends ISubscriptionRequest {
    public function setSubscriptionId(string $id);
    public function getSubscriptionId(): string;

    public function setProrationDate($date);
    public function getProrationDate(): ?core\time\IDate;
}

interface ISubscriptionFilter extends IFilter, ICustomerSubFilter {
    public function setPlanId(?string $planId);
    public function getPlanId(): ?string;

    public function setStatus(string $status);
    public function getStatus(): string;
}



// Subscription items
interface ISubscriptionItem {
    public function setItemId(?string $id);
    public function getItemId(): ?string;

    public function setPlanId(?string $id);
    public function getPlanId(): ?string;

    public function getKey(): string;

    public function setQuantity(int $quantity);
    public function getQuantity(): int;

    public function shouldDelete(bool $flag=null);
}

interface ISubscriptionItemRequest extends IRequest {}
interface ISubscriptionItemCreateRequest extends ISubscriptionRequest {}
interface ISubscriptionItemUpdateRequest extends ISubscriptionRequest {}
interface ISubscriptionItemDeleteRequest extends ISubscriptionRequest {}
interface ISubscriptionItemFilter extends IFilter {}
