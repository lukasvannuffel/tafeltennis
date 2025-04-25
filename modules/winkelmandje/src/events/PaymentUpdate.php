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
 * Handles payment status updates from Mollie and updates stash statuses accordingly.
 */
class PaymentUpdate {

    public static function handle() {
        // Listen for the payment save event for debugging purposes
        Event::on(
            Payment::class,
            MolliePayments::EVENT_BEFORE_PAYMENT_SAVE,
            function (PaymentUpdateEvent $event) {
                Craft::info('Payment save event triggered', __METHOD__);
                Craft::info('Payment data: ' . json_encode([
                    'id' => $event->payment->id ?? 'null',
                    'hasStash' => isset($event->payment->stash),
                    'stashValue' => $event->payment->stash ?? 'null'
                ]), __METHOD__);
            }
        );

        // Listen for the transaction update event
        Event::on(
            Transaction::class,
            MolliePayments::EVENT_AFTER_TRANSACTION_UPDATE,
            function (TransactionUpdateEvent $event) {
                // Log when we receive a transaction update
                Craft::info('Transaction update received with status: ' . $event->status, __METHOD__);
                
                // Only process paid transactions
                if ($event->status == "paid") {
                    $transactionId = $event->transaction['id'];
                    Craft::info('Processing paid transaction: ' . $transactionId, __METHOD__);
                    
                    // Update the related stash
                    self::updateStash($transactionId);
                }
            }
        );
    }
    
    /**
     * Updates a stash status to "paid" based on a completed transaction.
     *
     * @param string $transactionId The ID of the completed transaction
     * @return void
     */
    private static function updateStash($transactionId) {
        // Log the start of the update process
        Craft::info('Starting updateStash with transaction ID: ' . $transactionId, __METHOD__);
        
        // Get the payment ID from the transaction
        $transactionRecord = (new Query())
            ->select(['payment'])
            ->from('mollie_transactions')
            ->where(['id' => $transactionId])
            ->one();
        
        // Check if the transaction exists
        if (!$transactionRecord) {
            Craft::error('Transaction not found: ' . $transactionId, __METHOD__);
            return;
        }
        
        // Extract payment ID
        $paymentId = $transactionRecord['payment'];
        Craft::info('Found payment ID: ' . $paymentId, __METHOD__);
        
        // Find the payment
        $payment = Payment::find()
            ->id($paymentId)
            ->one();
        
        // Check if payment exists
        if (!$payment) {
            Craft::error('Payment not found: ' . $paymentId, __METHOD__);
            return;
        }
    
        // Log payment data for debugging
        Craft::info('Payment found with stash ID: ' . ($payment->stash ?? 'null'), __METHOD__);
    
        // Get the stash ID from the payment
        $stashId = $payment->stash;
    
        if (empty($stashId)) {
            Craft::error('No stash ID found in payment', __METHOD__);
            return;
        }
    
        // Find the stash by ID
        $stash = Entry::find()
            ->section('stash_section')
            ->id($stashId)
            ->one();
    
        // Check if we found the stash
        if (!$stash) {
            Craft::error('Stash not found with ID: ' . $stashId, __METHOD__);
            return;
        }
    
        Craft::info('Stash found. Updating status to paid for stash: ' . $stash->id, __METHOD__);
    
        // Update the title and status
        $stash->title = str_replace('OPENSTAAND', 'BETAALD', $stash->title);
        $stash->stash_status = "paid";
        
        // Save the stash with the new status
        if (!Craft::$app->elements->saveElement($stash)) {
            Craft::error('Failed to save stash element: ' . json_encode($stash->getErrors()), __METHOD__);
        } else {
            Craft::info('Successfully updated stash status to paid for stash ID: ' . $stash->id, __METHOD__);
        }
    }
}