<?php

namespace Modules\Notification\Tests\Unit;

use Modules\Notification\Services\Gateway\EmailGateway;
use Modules\Notification\Services\Gateway\GatewayFactory;
use Modules\Notification\Services\Gateway\SmsGateway;
use PHPUnit\Framework\TestCase;

class GatewayFactoryTest extends TestCase
{
    public function testCreateSmsGateway(): void
    {
        $factory = new GatewayFactory();
        $gateway = $factory->create('sms');

        $this->assertInstanceOf(SmsGateway::class, $gateway);
    }

    public function testCreateEmailGateway(): void
    {
        $factory = new GatewayFactory();
        $gateway = $factory->create('email');

        $this->assertInstanceOf(EmailGateway::class, $gateway);
    }

    public function testCreateInvalidChannelThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $factory = new GatewayFactory();
        $factory->create('invalid');
    }
}
