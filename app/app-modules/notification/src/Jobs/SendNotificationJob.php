<?php

namespace Modules\Notification\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Notification\Models\Notification;
use Modules\Notification\Models\NotificationRecipient;
use Modules\Notification\Services\Gateway\GatewayFactory;
use Modules\Notification\Models\DeliveryAttempt;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $maxExceptions = 3;

    public int $timeout = 30;

    /**
     * Create a new job instance.
     *
     * @param int $notificationId
     * @param int $recipientId
     */
    public function __construct(
        public int $notificationId,
        public int $recipientId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(GatewayFactory $gatewayFactory): void
    {
        $notification = Notification::find($this->notificationId);
        $recipient = NotificationRecipient::find($this->recipientId);

        if (!$notification || !$recipient) {
            Log::warning("Notification or recipient not found", [
                'notification_id' => $this->notificationId,
                'recipient_id' => $this->recipientId,
            ]);
            return;
        }

        // Idempotency check: if already processed, skip
        if ($recipient->status !== 'pending') {
            Log::info("Recipient already processed, skipping", [
                'recipient_id' => $this->recipientId,
                'status' => $recipient->status,
            ]);
            return;
        }

        $gateway = $gatewayFactory->create($notification->channel);

        $recipient->attempts++;
        $recipient->last_attempt_at = now();
        $recipient->save();

        $result = $gateway->send($notification->message_text, $recipient->recipient_id);

        DeliveryAttempt::create([
            'notification_recipient_id' => $recipient->id,
            'attempt_number' => $recipient->attempts,
            'provider_response' => json_encode($result),
        ]);

        if ($result['success']) {
            $recipient->status = 'sent';
            $recipient->completed_at = now();
            $recipient->save();

            Log::info("Notification sent successfully", [
                'notification_id' => $this->notificationId,
                'recipient_id' => $this->recipientId,
            ]);
        } else {
            if ($result['temporary_failure'] ?? false) {
                // Re-throw to trigger retry
                throw new \RuntimeException("Temporary failure: ".$result['message']);
            } else {
                $recipient->status = 'failed';
                $recipient->completed_at = now();
                $recipient->save();

                Log::error("Notification failed permanently", [
                    'notification_id' => $this->notificationId,
                    'recipient_id' => $this->recipientId,
                    'error' => $result['message'],
                ]);
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Job failed after all retries", [
            'notification_id' => $this->notificationId,
            'recipient_id' => $this->recipientId,
            'error' => $exception->getMessage(),
        ]);
    }
}
