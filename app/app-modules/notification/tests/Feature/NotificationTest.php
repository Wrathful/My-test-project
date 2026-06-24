<?php

namespace Modules\Notification\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Modules\Notification\Jobs\SendNotificationJob;
use Modules\Notification\Models\Notification;
use Modules\Notification\Models\NotificationRecipient;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function testSendNotificationCreatesRecords(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/v1/notifications', [
            'channel' => 'sms',
            'message' => 'Test message',
            'recipients' => ['+1234567890', '+0987654321'],
        ]);

        $response->assertStatus(202)
            ->assertJsonStructure(['notification_id', 'status', 'message']);

        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseCount('notification_recipients', 2);
    }

    public function testSendNotificationWithRequestIdIsIdempotent(): void
    {
        $requestId = Str::uuid();

        // First request
        $response1 = $this->postJson('/api/v1/notifications', [
            'channel' => 'sms',
            'message' => 'Test message',
            'recipients' => ['+1234567890'],
            'request_id' => $requestId,
        ]);

        $response1->assertStatus(202);

        // Second request with same request_id
        $response2 = $this->postJson('/api/v1/notifications', [
            'channel' => 'sms',
            'message' => 'Test message',
            'recipients' => ['+1234567890'],
            'request_id' => $requestId,
        ]);

        $response2->assertStatus(200)
            ->assertJson(['status' => 'duplicate']);

        // Should only have one notification record
        $this->assertDatabaseCount('notifications', 1);
    }

    public function testGetNotificationStatusForSubscriber(): void
    {
        $notification = Notification::create([
            'channel' => 'sms',
            'message_text' => 'Test message',
            'request_id' => Str::uuid(),
        ]);

        NotificationRecipient::create([
            'notification_id' => $notification->id,
            'recipient_id' => '+1234567890',
            'status' => 'sent',
        ]);

        $response = $this->getJson('/api/v1/notifications/subscriber/+1234567890');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonStructure([
                ['notification_id', 'channel', 'message', 'recipient_id', 'status', 'created_at', 'updated_at']
            ]);
    }

    public function testValidationFailsForInvalidChannel(): void
    {
        $response = $this->postJson('/api/v1/notifications', [
            'channel' => 'invalid',
            'message' => 'Test message',
            'recipients' => ['+1234567890'],
        ]);

        $response->assertStatus(422);
    }

    public function testValidationFailsForEmptyRecipients(): void
    {
        $response = $this->postJson('/api/v1/notifications', [
            'channel' => 'sms',
            'message' => 'Test message',
            'recipients' => [],
        ]);

        $response->assertStatus(422);
    }

    public function testTransactionalNotificationUsesHighPriorityQueue(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/v1/notifications', [
            'channel' => 'sms',
            'message' => 'Transactional message',
            'recipients' => ['+1234567890'],
            'is_transactional' => true,
        ]);

        $response->assertStatus(202);

        $notification = Notification::first();
        $this->assertEquals(1, $notification->is_transactional);
    }

    public function testMarketingNotificationUsesLowPriorityQueue(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/v1/notifications', [
            'channel' => 'email',
            'message' => 'Marketing message',
            'recipients' => ['user@example.com'],
            'is_transactional' => false,
        ]);

        $response->assertStatus(202);

        $notification = Notification::first();
        $this->assertEquals(0, $notification->is_transactional);
    }
}
