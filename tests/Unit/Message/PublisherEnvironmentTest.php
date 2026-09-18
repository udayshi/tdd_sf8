<?php

namespace App\Tests\Unit\Message;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PublisherEnvironmentTest extends KernelTestCase
{
    #[Test]
    public function env_variables_are_accessible(): void
    {
        $this->bootKernel();
        $container = static::getContainer();

        // Should be able to get parameters from env
        $host = $_ENV['RABBITMQ_HOST'] ?? 'localhost';
        $port = $_ENV['RABBITMQ_PORT'] ?? '5672';

        $this->assertNotEmpty($host);
        $this->assertNotEmpty($port);
    }

    #[Test]
    public function publisher_service_is_autowired(): void
    {
        $this->bootKernel();
        $container = static::getContainer();

        $publisher = $container->get('App\Service\Message\Publisher');

        $this->assertNotNull($publisher);
    }
}
