<?php

declare(strict_types=1);

namespace App\Tests\Functional\Repository;

use App\Entity\Todo;
use App\Entity\User;
use App\Repository\TodoRepository;
use App\Tests\DatabaseTestCase;
use PHPUnit\Framework\Attributes\Test;

class TodoRepositoryTest extends DatabaseTestCase
{
    private TodoRepository $repository;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = self::getContainer()->get(TodoRepository::class);

        $this->user = new User();
        $this->user->setEmail('test@example.com');
        $this->user->setPassword('hashed_password');
        $this->persist($this->user);
        $this->flush();
    }

    #[Test]
    public function canCreateAndPersistTodo(): void
    {
        $todo = new Todo();
        $todo->setTitle('Learn Symfony');
        $todo->setDescription('Complete the TDD guide');
        $todo->setUser($this->user);

        $this->persist($todo);
        $this->flush();

        $this->assertNotNull($todo->getId());
    }

    #[Test]
    public function canFindTodoById(): void
    {
        $todo = new Todo();
        $todo->setTitle('Learn TDD');
        $todo->setUser($this->user);
        $this->persist($todo);
        $this->flush();

        $foundTodo = $this->repository->find($todo->getId());

        $this->assertNotNull($foundTodo);
        $this->assertEquals('Learn TDD', $foundTodo->getTitle());
    }

    #[Test]
    public function canFindAllTodos(): void
    {
        $todo1 = new Todo();
        $todo1->setTitle('Todo 1');
        $todo1->setUser($this->user);
        $this->persist($todo1);

        $todo2 = new Todo();
        $todo2->setTitle('Todo 2');
        $todo2->setUser($this->user);
        $this->persist($todo2);

        $this->flush();

        $todos = $this->repository->findAll();

        $this->assertCount(2, $todos);
    }

    #[Test]
    public function canUpdateTodo(): void
    {
        $todo = new Todo();
        $todo->setTitle('Original Title');
        $todo->setUser($this->user);
        $this->persist($todo);
        $this->flush();

        $todo->setTitle('Updated Title');
        $this->flush();

        $foundTodo = $this->repository->find($todo->getId());
        $this->assertEquals('Updated Title', $foundTodo->getTitle());
    }

    #[Test]
    public function canDeleteTodo(): void
    {
        $todo = new Todo();
        $todo->setTitle('To Delete');
        $todo->setUser($this->user);
        $this->persist($todo);
        $this->flush();

        $todoId = $todo->getId();

        $this->entityManager->remove($todo);
        $this->flush();

        $foundTodo = $this->repository->find($todoId);
        $this->assertNull($foundTodo);
    }
}
