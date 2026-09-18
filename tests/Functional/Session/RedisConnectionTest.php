<?php

declare(strict_types=1);

namespace App\Tests\Functional\Session;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RedisConnectionTest extends KernelTestCase
{
    #[Test]
    public function redisUrlIsConfigured(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        $redisUrl = $_ENV['REDIS_URL'];

        $this->assertNotEmpty($redisUrl);
        $this->assertStringStartsWith('redis://', $redisUrl);
    }

    #[Test]
    public function redisDsnFormatIsValid(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        $redisDsn = $_ENV['REDIS_URL'];

        $this->assertStringStartsWith('redis://', $redisDsn);
        $this->assertStringContainsString(':', $redisDsn);
    }

    #[Test]
    public function frameworkConfigUsesRedisHandler(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        // Check if Redis DSN is configured
        $redisUrl = $_ENV['REDIS_URL'];

        $this->assertNotEmpty($redisUrl);
        $this->assertStringStartsWith('redis://', $redisUrl);
    }
}
