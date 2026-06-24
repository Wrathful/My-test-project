<?php

namespace Modules\Notification\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Notification\Http\Requests\SendNotificationRequest;
use Modules\Notification\Models\Notification;
use Modules\Notification\Models\NotificationRecipient;
use Modules\Notification\Jobs\SendNotificationJob;

class NotificationController extends Controller
{
    /**
     * Send notifications to multiple recipients.
     *
     * @param SendNotificationRequest $request
     * @return JsonResponse
     */
    public function store(SendNotificationRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $requestId = $validated['request_id'] ?? Str::uuid();
        $isTransactional = $validated['is_transactional'] ?? false;

        // Check idempotency: if request_id already processed recently, return existing notification
        $existingNotification = Notification::where('request_id', $requestId)->first();
        if ($existingNotification) {
            return response()->json([
                'notification_id' => $existingNotification->id,
                'status' => 'duplicate',
                'message' => 'Notification with this request_id was already processed.',
            ], 200);
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
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create notification and recipients: '.$e->getMessage());

            return response()->json([
                'error' => 'Failed to process notification request.',
            ], 500);
        }

        // Dispatch a job for each recipient
        foreach ($notification->recipients as $recipient) {
            // Transactional notifications get high priority (sent immediately)
            // Marketing notifications go to low priority queue
            $queue = $isTransactional ? 'notifications-high' : 'notifications-low';

            SendNotificationJob::dispatch($notification->id, $recipient->id)
                ->onQueue($queue);
        }

        return response()->json([
            'notification_id' => $notification->id,
            'status' => 'accepted',
            'message' => 'Notification queued for processing',
        ], 202);
    }

    /**
     * Get notification status for a specific subscriber.
     *
     * @param string $subscriberId
     * @param Request $request
     * @return JsonResponse
     */
    public function index(string $subscriberId, Request $request): JsonResponse
    {
        $limit = $request->query('limit', 50);
        $offset = $request->query('offset', 0);

        $notifications = NotificationRecipient::where('recipient_id', $subscriberId)
            ->with('notification')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(function ($recipient) {
                return [
                    'notification_id' => $recipient->notification_id,
                    'channel' => $recipient->notification->channel,
                    'message' => $recipient->notification->message_text,
                    'recipient_id' => $recipient->recipient_id,
                    'status' => $recipient->status,
                    'created_at' => $recipient->created_at,
                    'updated_at' => $recipient->updated_at,
                ];
            });

        return response()->json($notifications);
    }
}
