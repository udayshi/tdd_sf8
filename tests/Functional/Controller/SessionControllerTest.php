<?php

namespace App\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SessionControllerTest extends WebTestCase
{
    #[Test]
    public function session_data_can_be_set(): void
    {
        $client = static::createClient();

        $client->request('GET', '/session-test/set/test_key/test_value');

        $this->assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);
        $this->assertEquals('test_key', $response['data']['key']);
        $this->assertEquals('test_value', $response['data']['value']);
    }

    #[Test]
    public function session_data_can_be_retrieved(): void
    {
        $client = static::createClient();

        // Set session data
        $client->request('GET', '/session-test/set/username/testuser');
        $this->assertResponseIsSuccessful();

        // Retrieve session data
        $client->request('GET', '/session-test/get/username');
        $this->assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);
        $this->assertEquals('username', $response['key']);
        $this->assertEquals('testuser', $response['value']);
    }

    #[Test]
    public function session_all_returns_session_data(): void
    {
        $client = static::createClient();

        // Set multiple session values
        $client->request('GET', '/session-test/set/key1/value1');
        $client->request('GET', '/session-test/set/key2/value2');

        // Get all session data
        $client->request('GET', '/session-test/all');
        $this->assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);
        $this->assertIsArray($response['data']);
    }
}
