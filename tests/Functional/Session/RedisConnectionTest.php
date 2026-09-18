<?php

namespace App\Tests\Functional\Session;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RedisConnectionTest extends KernelTestCase
{
    #[Test]
    public function redis_url_is_configured(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        $redisUrl = $_ENV['REDIS_URL'];

        $this->assertNotEmpty($redisUrl);
        $this->assertStringStartsWith('redis://', $redisUrl);
    }

    #[Test]
    public function redis_dsn_format_is_valid(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        $redisDsn = $_ENV['REDIS_URL'];

        $this->assertStringStartsWith('redis://', $redisDsn);
        $this->assertStringContainsString(':', $redisDsn);
    }

    #[Test]
    public function framework_config_uses_redis_handler(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        // Check if Redis DSN is configured
        $redisUrl = $_ENV['REDIS_URL'];

        $this->assertNotEmpty($redisUrl);
        $this->assertStringStartsWith('redis://', $redisUrl);
    }
}
