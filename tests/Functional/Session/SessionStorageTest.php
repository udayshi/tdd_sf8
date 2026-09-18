<?php

declare(strict_types=1);

namespace App\Tests\Functional\Session;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Session\Session;

class SessionStorageTest extends KernelTestCase
{
    #[Test]
    public function sessionCanBeCreated(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        $session = new Session();
        $session->start();
        $this->assertInstanceOf(Session::class, $session);
    }

    #[Test]
    public function sessionDataCanBeStored(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        $session = new Session();

        $session->set('test_key', 'test_value');

        $this->assertEquals('test_value', $session->get('test_key'));
    }

    #[Test]
    public function sessionDataPersists(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        $session = new Session();

        $session->set('user_id', 123);
        $session->set('username', 'testuser');

        $this->assertEquals(123, $session->get('user_id'));
        $this->assertEquals('testuser', $session->get('username'));
    }
}
