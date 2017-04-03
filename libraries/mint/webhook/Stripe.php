<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\mint\webhook;

use df;
use df\core;
use df\mint;
use df\arch;

class Stripe extends arch\node\RestApi implements mint\IWebhookNode {

    const EVENTS = [];

    public function executePost() {

    }


/*
 account.updated
 account.application.deauthorized
 account.external_account.created
 account.external_account.deleted
 account.external_account.updated
 application_fee.created
 application_fee.refunded
 application_fee.refund.updated
 balance.available
 bitcoin.receiver.created
 bitcoin.receiver.filled
 bitcoin.receiver.updated
 bitcoin.receiver.transaction.created
 charge.captured
 charge.failed
 charge.pending
 charge.refunded
 charge.succeeded
 charge.updated
 charge.dispute.closed
 charge.dispute.created
 charge.dispute.funds_reinstated
 charge.dispute.funds_withdrawn
 charge.dispute.updated
 coupon.created
 coupon.deleted
 coupon.updated
 customer.created
 customer.deleted
 customer.updated
 customer.discount.created
 customer.discount.deleted
 customer.discount.updated
 customer.source.created
 customer.source.deleted
 customer.source.updated
 customer.subscription.created
 customer.subscription.deleted
 customer.subscription.trial_will_end
 customer.subscription.updated
 invoice.created
 invoice.payment_failed
 invoice.payment_succeeded
 invoice.sent
 invoice.updated
 invoiceitem.created
 invoiceitem.deleted
 invoiceitem.updated
 order.created
 order.payment_failed
 order.payment_succeeded
 order.updated
 order_return.created
 plan.created
 plan.deleted
 plan.updated
 product.created
 product.deleted
 product.updated
 recipient.created
 recipient.deleted
 recipient.updated
 review.closed
 review.opened
 sku.created
 sku.deleted
 sku.updated
 source.canceled
 source.chargeable
 source.failed
 source.transaction.created
 transfer.created
 transfer.failed
 transfer.paid
 transfer.reversed
 transfer.updated
 ping
*/
}