<?php

namespace Modules\Notification\Actions;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Modules\Notification\Models\Notification;
use Modules\Notification\Models\NotificationRecipient;
use Modules\Notification\Jobs\SendNotificationJob;

class CreateNotificationAction
{
    /**
     * Create a notification and dispatch jobs.
     *
     * @param array $validated
     * @return array{
     *     notification: \Modules\Notification\Models\Notification,
     *     created: bool
     * }
     */
    public function execute(array $validated)
    {
        $requestId = $validated['request_id'] ?? Str::uuid();
        $isTransactional = $validated['is_transactional'] ?? false;

        // Idempotency check using Redis
        $cacheKey = "notification_request_{$requestId}";
        if (Cache::has($cacheKey)) {
            $notificationId = Cache::get($cacheKey);
            $notification = Notification::find($notificationId);
            if ($notification) {
                return [
                    'notification' => $notification,
                    'created' => false,
                ];
            }
            // If notification not found (should not happen), fall through to create new
        }

        DB::beginTransaction();
        try {
            $notification = Notification::create([
                'channel' => $validated['channel'],
                'message_text' => $validated['message'],
                'request_id' => $requestId,
                'is_transactional' => $isTransactional,
            ]);

            foreach ($validated['recipients'] as $recipientId) {
                NotificationRecipient::create([
                    'notification_id' => $notification->id,
                    'recipient_id' => $recipientId,
                    'status' => 'pending',
                ]);
            }

            DB::commit();

            // Store in Redis for idempotency with TTL 24 hours
            Cache::put($cacheKey, $notification->id, now()->addHours(24));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create notification and recipients: '.$e->getMessage());

            throw $e;
        }

        // Dispatch a job for each recipient
        foreach ($notification->recipients as $recipient) {
            $queue = $isTransactional ? 'notifications-high' : 'notifications-low';

            SendNotificationJob::dispatch($notification->id, $recipient->id)
                ->onQueue($queue);
        }

        return [
            'notification' => $notification,
            'created' => true,
        ];
    }
}
