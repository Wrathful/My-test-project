<?php

namespace Modules\Notification\Services\Gateway;

use InvalidArgumentException;

class GatewayFactory
{
    /**
     * Create a gateway instance based on the channel.
     *
     * @param string $channel
     * @return GatewayInterface
     * @throws InvalidArgumentException
     */
    public function create(string $channel): GatewayInterface
    {
        return match ($channel) {
            'sms' => new SmsGateway(),
            'email' => new EmailGateway(),
            default => throw new InvalidArgumentException("Unknown channel: {$channel}"),
        };
    }
}
