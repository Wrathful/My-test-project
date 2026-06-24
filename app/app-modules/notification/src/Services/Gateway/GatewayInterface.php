<?php

namespace Modules\Notification\Services\Gateway;

interface GatewayInterface
{
    /**
     * Send a notification to the specified recipient.
     *
     * @param string $message
     * @param string $recipient
     * @return array{success: bool, message: string, temporary_failure?: bool}
     */
    public function send(string $message, string $recipient): array;
}
