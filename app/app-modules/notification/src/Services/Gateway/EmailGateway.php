<?php

namespace Modules\Notification\Services\Gateway;

class EmailGateway implements GatewayInterface
{
    /**
     * Send an email message to the specified email address.
     *
     * @param string $message
     * @param string $recipient
     * @return array{success: bool, message: string, temporary_failure?: bool}
     */
    public function send(string $message, string $recipient): array
    {
        // Mock implementation - simulates successful email sending
        // In production, this would integrate with SendGrid, AWS SES, etc.

        // Simulate invalid email
        if (!str_contains($recipient, '@')) {
            return [
                'success' => false,
                'message' => 'Invalid email address',
                'temporary_failure' => false,
            ];
        }

        return [
            'success' => true,
            'message' => 'Email sent successfully',
        ];
    }
}
