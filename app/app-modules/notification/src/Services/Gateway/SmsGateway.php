<?php

namespace Modules\Notification\Services\Gateway;

class SmsGateway implements GatewayInterface
{
    /**
     * Send an SMS message to the specified phone number.
     *
     * @param string $message
     * @param string $recipient
     * @return array{success: bool, message: string, temporary_failure?: bool}
     */
    public function send(string $message, string $recipient): array
    {
        // Mock implementation - simulates successful SMS sending
        // In production, this would integrate with Twilio, AWS SNS, etc.

        // Simulate invalid phone number
        if (str_starts_with($recipient, '+999')) {
            return [
                'success' => false,
                'message' => 'Invalid phone number',
                'temporary_failure' => false,
            ];
        }

        return [
            'success' => true,
            'message' => 'SMS sent successfully',
        ];
    }
}
