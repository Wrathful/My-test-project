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
            'sms' => \app(SmsGateway::class),
            'email' => \app(EmailGateway::class),
            default => throw new InvalidArgumentException("Unknown channel: {$channel}"),
        };
    }
}
