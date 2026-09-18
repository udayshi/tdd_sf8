<?php

namespace App\Tests\Controller\Api;

use App\Entity\Todo;
use App\Entity\User;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use PHPUnit\Framework\Attributes\Test;

class TodoControllerTest extends WebTestCase
{
    private User $testUser;
    private ?KernelBrowser $client = null;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->setupDatabase();

        $this->testUser = new User();
        $this->testUser->setEmail('test@example.com');
        $this->testUser->setPassword('hashed_password');
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $em->persist($this->testUser);
        $em->flush();
    }

    private function setupDatabase(): void
    {
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $metadataFactory = $em->getMetadataFactory();
        $schemaTool = new SchemaTool($em);

        try {
            $schemaTool->dropSchema($metadataFactory->getAllMetadata());
        } catch (\Exception) {
            // Schema doesn't exist
        }

        $schemaTool->createSchema($metadataFactory->getAllMetadata());
    }

    #[Test]
    public function can_list_todos(): void
    {
        $this->client->request('GET', '/api/todos');

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertIsArray($data['data']);
    }

    #[Test]
    public function can_create_todo(): void
    {
        $this->client->request('POST', '/api/todos', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Test Todo',
            'description' => 'Test Description',
        ]));

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('Test Todo', $data['data']['title']);
    }

    #[Test]
    public function cannot_create_todo_without_title(): void
    {
        $this->client->request('POST', '/api/todos', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'description' => 'No title',
        ]));

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }

    #[Test]
    public function can_show_todo(): void
    {
        $todo = new Todo();
        $todo->setTitle('Show Test');
        $todo->setUser($this->testUser);
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $em->persist($todo);
        $em->flush();

        $this->client->request('GET', "/api/todos/{$todo->getId()}");

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Show Test', $data['data']['title']);
    }

    #[Test]
    public function can_update_todo(): void
    {
        $todo = new Todo();
        $todo->setTitle('Original');
        $todo->setUser($this->testUser);
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $em->persist($todo);
        $em->flush();

        $this->client->request('PUT', "/api/todos/{$todo->getId()}", [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Updated',
        ]));

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Updated', $data['data']['title']);
    }

    #[Test]
    public function can_delete_todo(): void
    {
        $todo = new Todo();
        $todo->setTitle('To Delete');
        $todo->setUser($this->testUser);
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $em->persist($todo);
        $em->flush();

        $todoId = $todo->getId();

        $this->client->request('DELETE', "/api/todos/{$todoId}");

        $this->assertResponseStatusCodeSame(204);
    }
}
