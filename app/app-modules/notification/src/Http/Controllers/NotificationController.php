<?php

namespace Modules\Notification\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Notification\Http\Requests\SendNotificationRequest;
use Modules\Notification\Actions\CreateNotificationAction;

class NotificationController extends Controller
{
    /**
     * Create notification action.
     */
    protected CreateNotificationAction $createNotificationAction;

    /**
     * Constructor.
     *
     * @param  CreateNotificationAction  $createNotificationAction
     */
    public function __construct(CreateNotificationAction $createNotificationAction)
    {
        $this->createNotificationAction = $createNotificationAction;
    }

    /**
     * Send notifications to multiple recipients.
     *
     * @param SendNotificationRequest $request
     * @return JsonResponse
     */
    public function store(SendNotificationRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->createNotificationAction->execute($validated);

        if (! $result['created']) {
            return response()->json([
                'notification_id' => $result['notification']->id,
                'status' => 'duplicate',
                'message' => 'Notification with this request_id was already processed.',
            ], 200);
        }

        return response()->json([
            'notification_id' => $result['notification']->id,
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

        $notifications = \Modules\Notification\Models\NotificationRecipient::where('recipient_id', $subscriberId)
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
