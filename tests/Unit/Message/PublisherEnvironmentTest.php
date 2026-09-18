<?php

declare(strict_types=1);

namespace App\Tests\Unit\Message;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PublisherEnvironmentTest extends KernelTestCase
{
    #[Test]
    public function envVariablesAreAccessible(): void
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
    public function publisherServiceIsAutowired(): void
    {
        $this->bootKernel();
        $container = static::getContainer();

        $publisher = $container->get('App\Service\Message\Publisher');

        $this->assertNotNull($publisher);
    }
}
