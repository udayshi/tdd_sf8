<?php

declare(strict_types=1);

namespace App\Tests\Functional\Session;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CompleteWorkflowTest extends WebTestCase
{
    #[Test]
    public function completeSessionLifecycle(): void
    {
        $client = static::createClient();

        // Set session data
        $client->request('GET', '/session-test/set/user_id/42');
        $this->assertResponseIsSuccessful();

        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($content['success']);
        $this->assertEquals('42', $content['data']['value']);
    }

    #[Test]
    public function sessionPersistsAcrossMultipleRequests(): void
    {
        $client = static::createClient();

        // Set data in first request
        $client->request('GET', '/session-test/set/username/testuser');
        $this->assertResponseIsSuccessful();

        // Retrieve in second request
        $client->request('GET', '/session-test/get/username');
        $this->assertResponseIsSuccessful();

        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('testuser', $content['value']);
    }

    #[Test]
    public function multipleSessionValuesCanCoexist(): void
    {
        $client = static::createClient();

        // Set multiple values
        $client->request('GET', '/session-test/set/key1/value1');
        $client->request('GET', '/session-test/set/key2/value2');
        $client->request('GET', '/session-test/set/key3/value3');

        // Get all
        $client->request('GET', '/session-test/all');
        $this->assertResponseIsSuccessful();

        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(3, $content['data']);
    }

    #[Test]
    public function sessionCanBeDestroyed(): void
    {
        $client = static::createClient();

        // Set data
        $client->request('GET', '/session-test/set/data/value');

        // Destroy session
        $client->request('GET', '/session-test/destroy');
        $this->assertResponseIsSuccessful();

        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($content['success']);
        $this->assertStringContainsString('destroyed', $content['message']);
    }
}
