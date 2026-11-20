<?php

namespace App\Http\Controllers;

use App\Contracts\RazorpayServiceInterface;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(private RazorpayServiceInterface $razorpayService) {}
    public function handleRazorpay(Request $request)
    {
        // Get raw payload and signature
        $payload   = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature');

        //  Verify webhook signature
        if (!$this->razorpayService->verifyWebhookSignature($payload, $signature)) {
            Log::warning('Invalid Razorpay webhook signature', [
                'ip'        => $request->ip(),
                'signature' => $signature,
            ]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Parse the event
        $event = $request->json()->all();

        Log::info('Razorpay webhook received', [
            'event'   => $event['event'] ?? 'unknown',
            'payload' => array_keys($event['payload'] ?? []),
        ]);

        // Handle specific events
        try {
            match ($event['event'] ?? '') {
                'payment.authorized'      => $this->handlePaymentAuthorized($event),
                'payment.captured'        => $this->handlePaymentCaptured($event),
                'payment.failed'          => $this->handlePaymentFailed($event),
                'refund.created'          => $this->handleRefundCreated($event),
                'refund.processed'        => $this->handleRefundProcessed($event),
                'refund.failed'           => $this->handleRefundFailed($event),
                'order.paid'              => $this->handleOrderPaid($event),
                'payment.dispute.created' => $this->handleDisputeCreated($event),
                default                   => $this->handleUnknownEvent($event),
            };
        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'event' => $event['event'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            // Return 200 to prevent Razorpay from retrying
            // Log the error for manual investigation
            return response()->json(['status' => 'error_logged'], 200);
        }
        // Always return 200 to Razorpay
        // Otherwise Razorpay will retry the webhook
        return response()->json(['status' => 'processed'], 200);
    }

    private function handlePaymentAuthorized(array $event): void
    {
        $paymentData = $event['payload']['payment']['entity'];
        Log::info('Payment authorized', ['payment_id' => $paymentData['id']]);

        // Payment authorized but not yet captured
        // In auto-capture mode this fires before captured
        $payment = Payment::where('razorpay_order_id', $paymentData['order_id'])->first();

        if ($payment) {
            $payment->update([
                'status'              => 'attempted',
                'razorpay_payment_id' => $paymentData['id'],
            ]);
        }
    }

    private function handlePaymentCaptured(array $event): void
    {
        $paymentData = $event['payload']['payment']['entity'];

        Log::info('Payment captured via webhook', [
            'payment_id' => $paymentData['id'],
            'order_id'   => $paymentData['order_id'],
            'amount'     => $paymentData['amount'] / 100,
        ]);

        $payment = Payment::where('razorpay_order_id', $paymentData['order_id'])->first();

        if (!$payment) {
            Log::error('Payment not found for webhook', ['razorpay_order_id' => $paymentData['order_id']]);
            return;
        }

        // Avoid duplicate processing — check if already paid
        if ($payment->status === 'paid') {
            Log::info('Payment already captured, skipping webhook', ['payment_id' => $payment->id]);
            return;
        }

        // Update payment record
        $payment->update([
            'razorpay_payment_id' => $paymentData['id'],
            'status'              => 'paid',
            'method'              => $paymentData['method'],
            'bank'                => $paymentData['bank'] ?? null,
            'wallet'              => $paymentData['wallet'] ?? null,
            'vpa'                 => $paymentData['vpa'] ?? null,
            'card_network'        => $paymentData['card']['network'] ?? null,
            'card_last4'          => $paymentData['card']['last4'] ?? null,
            'razorpay_response'   => $paymentData,
            'paid_at'             => now(),
        ]);

        // Update order
        $payment->order->update([
            'payment_status' => 'paid',
            'status'         => 'confirmed',
        ]);

        // Log status history
        $payment->order->statusHistories()->create([
            'status' => 'confirmed',
            'note'   => "Payment captured via webhook. Method: {$paymentData['method']}",
        ]);

        // Send notifications
        // $payment->order->user->notify(
        //     new \App\Notifications\OrderPlacedNotification($payment->order)
        // );
    }

    private function handlePaymentFailed(array $event): void
    {
        $paymentData = $event['payload']['payment']['entity'];
        $errorData   = $event['payload']['payment']['entity']['error_code'] ?? 'UNKNOWN';

        Log::warning('Payment failed via webhook', [
            'payment_id'  => $paymentData['id'],
            'order_id'    => $paymentData['order_id'],
            'error_code'  => $errorData,
        ]);

        $payment = Payment::where('razorpay_order_id', $paymentData['order_id'])->first();

        if ($payment) {
            $payment->update([
                'razorpay_payment_id' => $paymentData['id'],
                'status'              => 'failed',
                'failure_reason'      => $paymentData['error_description'] ?? 'Payment failed',
                'razorpay_response'   => $paymentData,
            ]);

            $payment->order->update(['payment_status' => 'failed']);

            // Notify customer about payment failure
            // $payment->order->user->notify(
            //     new \App\Notifications\PaymentFailedNotification($payment)
            // );
        }
    }

    private function handleRefundCreated(array $event): void
    {
        $refundData = $event['payload']['refund']['entity'];

        Log::info('Refund created via webhook', [
            'refund_id'  => $refundData['id'],
            'payment_id' => $refundData['payment_id'],
            'amount'     => $refundData['amount'] / 100,
        ]);

        // Update refund status if exists
        $refund = Refund::where('razorpay_refund_id', $refundData['id'])->first();

        if ($refund) {
            $refund->update(['status' => 'pending']);
        }
    }

    private function handleRefundProcessed(array $event): void
    {
        $refundData = $event['payload']['refund']['entity'];

        Log::info('Refund processed via webhook', [
            'refund_id' => $refundData['id'],
            'amount'    => $refundData['amount'] / 100,
        ]);

        $refund = Refund::where('razorpay_refund_id', $refundData['id'])->first();

        if ($refund) {
            $refund->update([
                'status'       => 'processed',
                'processed_at' => now(),
            ]);

            // Notify customer about refund
            // $refund->order->user->notify(
            //     new \App\Notifications\RefundProcessedNotification($refund)
            // );
        }
    }

    private function handleRefundFailed(array $event): void
    {
        $refundData = $event['payload']['refund']['entity'];

        Log::error('Refund failed via webhook', [
            'refund_id' => $refundData['id'],
        ]);

        $refund = Refund::where('razorpay_refund_id', $refundData['id'])->first();
        $refund?->update(['status' => 'failed']);
    }

    private function handleOrderPaid(array $event): void
    {
        $orderData = $event['payload']['order']['entity'];

        Log::info('Order paid via webhook', [
            'razorpay_order_id' => $orderData['id'],
        ]);

        // This is the final confirmation that the Razorpay order is fully paid
        $payment = Payment::where('razorpay_order_id', $orderData['id'])->first();

        if ($payment && $payment->status !== 'paid') {
            $payment->update(['status' => 'paid']);
            $payment->order->update(['payment_status' => 'paid']);
        }
    }

    private function handleDisputeCreated(array $event): void
    {
        Log::critical('Payment dispute created!', [
            'event' => $event,
        ]);

        // Alert admin immediately about dispute
        // $admins = \App\Models\User::where('role', 'admin')->get();
        // foreach ($admins as $admin) {
        //     $admin->notify(
        //         new \App\Notifications\PaymentDisputeNotification($event)
        //     );
        // }
    }


    private function handleUnknownEvent(array $event): void
    {
        Log::info('Unknown Razorpay webhook event', [
            'event' => $event['event'] ?? 'unknown',
        ]);
    }
}
