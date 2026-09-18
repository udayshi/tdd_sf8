<?php

namespace App\Tests\Unit\Message;

use App\Service\Message\Publisher;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PublisherTest extends TestCase
{
    private Publisher $publisher;

    protected function setUp(): void
    {
        // This will fail until Publisher exists
        $this->publisher = new Publisher('localhost', 5672, 'root', 'rootpass');
    }

    #[Test]
    public function can_instantiate_publisher(): void
    {
        $this->assertInstanceOf(Publisher::class, $this->publisher);
    }

    #[Test]
    public function can_publish_message(): void
    {
        // Won't fail on actual RabbitMQ connection - just test method exists
        $this->assertTrue(method_exists($this->publisher, 'publish'));
    }

    #[Test]
    public function publish_accepts_message_and_routing_key(): void
    {
        $reflection = new \ReflectionMethod(Publisher::class, 'publish');
        $params = $reflection->getParameters();

        $this->assertCount(2, $params);
        $this->assertEquals('message', $params[0]->getName());
        $this->assertEquals('routingKey', $params[1]->getName());
    }
}
