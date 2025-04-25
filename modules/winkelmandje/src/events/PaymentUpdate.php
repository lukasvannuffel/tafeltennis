<?php

namespace modules\winkelmandje\events;

use Craft;
use craft\db\Query;
use craft\elements\Entry;
use studioespresso\molliepayments\events\TransactionUpdateEvent;
use studioespresso\molliepayments\events\PaymentUpdateEvent;
use studioespresso\molliepayments\MolliePayments;
use yii\base\Event;
use studioespresso\molliepayments\elements\Payment;
use studioespresso\molliepayments\services\Transaction;

/**
 * PaymentUpdate class.
 */
class PaymentUpdate {

    public static function handle() {
        Event::on(
            Payment::class,
            MolliePayments::EVENT_BEFORE_PAYMENT_SAVE,
            function (PaymentUpdateEvent $event) {
                // handle the event here
            }
        );

        // listen for the transaction update event
        Event::on(
            Transaction::class,
            MolliePayments::EVENT_AFTER_TRANSACTION_UPDATE,
            function (TransactionUpdateEvent $event) {
                // Enhanced logging
                Craft::info('Transaction update received with status: ' . $event->status, __METHOD__);
                
                // check if the status is paid
                if($event->status == "paid") {
                    // obtain the transaction ID from the event
                    $transactionUId = $event->transaction['id'];
                    Craft::info('Processing paid transaction: ' . $transactionUId, __METHOD__);
                    
                    // finally, update the related stash
                    self::updateStash($transactionUId);
                }
            }
        );
    
        // Also listen for the payment save event to debug
        Event::on(
            Payment::class,
            MolliePayments::EVENT_BEFORE_PAYMENT_SAVE,
            function (PaymentUpdateEvent $event) {
                // Log payment details for debugging
                Craft::info('Payment save event triggered', __METHOD__);
                Craft::info('Payment has stash: ' . ($event->payment->stash ? 'yes' : 'no'), __METHOD__);
                if ($event->payment->stash) {
                    Craft::info('Stash ID in payment: ' . $event->payment->stash, __METHOD__);
                }
            }
        );
    }
    
    private static function updateStash($transactionId) {
        // Enhanced logging
        Craft::info('Starting updateStash with transaction ID: ' . $transactionId, __METHOD__);
        
        // get the payment ID from the transaction
        $transactionRecord = (new Query())
            ->select(['payment'])
            ->from('mollie_transactions')
            ->where(['id' => $transactionId])
            ->one();
        
        // check if the transaction was found
        if ($transactionRecord) {
            // get the payment ID
            $paymentId = $transactionRecord['payment'];
            Craft::info('Found payment ID: ' . $paymentId, __METHOD__);
        } else {
            // log an error and return
            Craft::error('Transaction not found: ' . $transactionId, __METHOD__);
            return;
        }
    
        // find the payment
        $payment = Payment::find()
            ->id($paymentId)
            ->one();
        
        // check if the payment was found
        if (!$payment) {
            Craft::error('Payment not found: ' . $paymentId, __METHOD__);
            return;
        }
    
        // Log payment data for debugging
        Craft::info('Payment found. Payment data: ' . json_encode([
            'id' => $payment->id,
            'hasStash' => isset($payment->stash),
            'stashValue' => $payment->stash ?? 'null'
        ]), __METHOD__);
    
        // get the stash ID from the payment
        $stashId = $payment->stash;
    
        if (!$stashId) {
            Craft::error('No stash ID found in payment', __METHOD__);
            return;
        }
    
        // find the stash
        $stash = Entry::find()
            ->section('stash_section')
            ->uid($stashId)
            ->one();
    
        // check if the stash was found
        if (!$stash) {
            Craft::error('Stash not found: ' . $stashId, __METHOD__);
            
            // Try to find by ID instead of UID as a fallback
            $stash = Entry::find()
                ->section('stash_section')
                ->id($stashId)
                ->one();
                
            if (!$stash) {
                Craft::error('Stash not found by ID either: ' . $stashId, __METHOD__);
                return;
            } else {
                Craft::info('Stash found by ID instead of UID', __METHOD__);
            }
        }
    
        Craft::info('Stash found. Updating status to paid for stash: ' . $stash->id, __METHOD__);
    
        // title is in this format: [OPENSTAAND] Stash voor somebody (5 items)
        // change OPENSTAAND to BETAALD
        $stash->title = str_replace('OPENSTAAND', 'BETAALD', $stash->title);
        $stash->stash_status = "paid";
        if (!Craft::$app->elements->saveElement($stash)) {
            Craft::error('Failed to save stash element: ' . $stashId . '. Errors: ' . json_encode($stash->getErrors()), __METHOD__);
        } else {
            Craft::info('Successfully updated stash status to paid', __METHOD__);
        }
    }
}