<?php

namespace App\Tests\Functional;

use App\Entity\Todo;
use App\Entity\User;
use App\Repository\TodoRepository;
use App\Service\TodoService;
use App\Tests\DatabaseTestCase;
use PHPUnit\Framework\Attributes\Test;

class SmokeTest extends DatabaseTestCase
{
    private TodoService $service;
    private TodoRepository $repository;
    private User $testUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = self::getContainer()->get(TodoService::class);
        $this->repository = self::getContainer()->get(TodoRepository::class);

        $this->testUser = new User();
        $this->testUser->setEmail('smoke@example.com');
        $this->testUser->setPassword('hashed_password');
        $this->persist($this->testUser);
        $this->flush();
    }

    #[Test]
    public function complete_todo_workflow(): void
    {
        // Create
        $todo = $this->service->createTodo('Complete Symfony TDD', 'Finish all phases');
        $todo->setUser($this->testUser);
        $this->persist($todo);
        $this->flush();

        $this->assertNotNull($todo->getId());
        $this->assertEquals('Complete Symfony TDD', $todo->getTitle());
        $this->assertFalse($todo->isCompleted());

        // Find
        $found = $this->repository->find($todo->getId());
        $this->assertNotNull($found);

        // Update
        $this->service->updateTodo($found, 'Finish Symfony TDD Guide');
        $this->flush();

        $updated = $this->repository->find($todo->getId());
        $this->assertEquals('Finish Symfony TDD Guide', $updated->getTitle());

        // Toggle
        $this->service->toggleCompletion($updated);
        $this->flush();

        $completed = $this->repository->find($todo->getId());
        $this->assertTrue($completed->isCompleted());
    }

    #[Test]
    public function multiple_todos_workflow(): void
    {
        $todo1 = $this->service->createTodo('Task 1', 'Desc 1');
        $todo1->setUser($this->testUser);
        $this->persist($todo1);

        $todo2 = $this->service->createTodo('Task 2', 'Desc 2');
        $todo2->setUser($this->testUser);
        $this->persist($todo2);

        $this->flush();

        $allTodos = $this->repository->findAll();
        $this->assertCount(2, $allTodos);
    }

    #[Test]
    public function todo_deletion_workflow(): void
    {
        $todo = $this->service->createTodo('To Delete');
        $todo->setUser($this->testUser);
        $this->persist($todo);
        $this->flush();

        $todoId = $todo->getId();
        $found = $this->repository->find($todoId);
        $this->assertNotNull($found);

        $this->entityManager->remove($todo);
        $this->flush();

        $notFound = $this->repository->find($todoId);
        $this->assertNull($notFound);
    }
}
