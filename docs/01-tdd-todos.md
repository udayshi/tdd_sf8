
## Table of Contents

1. [Project Setup & Verification](#project-setup--verification)
2. [Phase 1: Unit Tests (Fast Feedback)](#phase-1-unit-tests-fast-feedback)
3. [Phase 2: Integration Tests (Database Layer)](#phase-2-integration-tests-database-layer)
4. [Phase 3: API Tests (Full Feature)](#phase-3-api-tests-full-feature)
5. [Phase 4: Smoke Tests (End-to-End)](#phase-4-smoke-tests-end-to-end)
6. [Running Tests & Debugging](#running-tests--debugging)

---

## Project Setup & Verification

### Environment Verification

```bash
php --version    # Should be 8.4+
composer --version
symfony --version  # Optional: Symfony CLI
```

### Current Stack

This project uses:
- **Symfony**: 8.1.* (full stack with Doctrine ORM)
- **PHP**: 8.4+
- **PHPUnit**: 13.3+
- **Doctrine ORM**: 3.7+
- **Database (Dev)**: PostgreSQL 16
- **Database (Tests)**: SQLite in-memory (fastest)

### Install & Run Tests

```bash
composer install
bin/phpunit
```



### Key Configuration Files

- **phpunit.dist.xml**: Test environment setup
  - `APP_ENV=test`
  - `DATABASE_URL=sqlite:///:memory:` (fast, no I/O)
- **src/**: Source code (entities, services, controllers)
- **tests/**: Test files (unit, functional, integration)

---

## Phase 1: Unit Tests (Fast Feedback)


### 1.1: Entity Class (Plain PHP, No DB)

`src/Entity/Todo.php`:

```php
<?php

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\TodoRepository::class)]
#[ORM\Table(name: 'todo')]
class Todo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'boolean')]
    private bool $completed = false;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'todos')]
    private ?User $user = null;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function isCompleted(): bool
    {
        return $this->completed;
    }

    public function setCompleted(bool $completed): self
    {
        $this->completed = $completed;
        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAt(?DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }
}
```

### 1.2: Write Unit Test

`tests/Unit/TodoTest.php`:

```php
<?php

namespace App\Tests\Unit;

use App\Entity\Todo;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TodoTest extends TestCase
{
    #[Test]
    public function can_create_todo_with_title(): void
    {
        $todo = new Todo();
        $todo->setTitle('Buy groceries');

        $this->assertEquals('Buy groceries', $todo->getTitle());
    }

    #[Test]
    public function can_set_description(): void
    {
        $todo = new Todo();
        $todo->setTitle('Buy groceries');
        $todo->setDescription('Milk, eggs, bread');

        $this->assertEquals('Milk, eggs, bread', $todo->getDescription());
    }

    #[Test]
    public function has_created_at_timestamp(): void
    {
        $todo = new Todo();

        $this->assertNotNull($todo->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $todo->getCreatedAt());
    }
}
```

### 1.3: Run Unit Test

```bash
bin/phpunit tests/Unit/TodoTest.php
```

**Expected output:**
```
OK (3 tests, 4 assertions)
```

### 1.4: Service Class (Business Logic)

`src/Service/TodoService.php`:

```php
<?php

namespace App\Service;

use App\Entity\Todo;

class TodoService
{
    public function createTodo(string $title, ?string $description = null): Todo
    {
        if (empty($title)) {
            throw new \InvalidArgumentException('Title cannot be empty');
        }

        $todo = new Todo();
        $todo->setTitle(trim($title));
        $todo->setDescription($description);

        return $todo;
    }

    public function toggleCompletion(Todo $todo): void
    {
        $todo->setCompleted(!$todo->isCompleted());
    }

    public function updateTodo(
        Todo $todo,
        string $title,
        ?string $description = null
    ): void {
        if (empty($title)) {
            throw new \InvalidArgumentException('Title cannot be empty');
        }

        $todo->setTitle(trim($title));
        $todo->setDescription($description);
    }
}
```

### 1.5: Service Tests

`tests/Unit/TodoServiceTest.php`:

```php
<?php

namespace App\Tests\Unit;

use App\Entity\Todo;
use App\Service\TodoService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TodoServiceTest extends TestCase
{
    private TodoService $service;

    protected function setUp(): void
    {
        $this->service = new TodoService();
    }

    #[Test]
    public function can_create_new_todo(): void
    {
        $title = 'Learn Symfony';
        $description = 'Complete TDD guide';

        $todo = $this->service->createTodo($title, $description);

        $this->assertEquals($title, $todo->getTitle());
        $this->assertEquals($description, $todo->getDescription());
        $this->assertFalse($todo->isCompleted());
    }

    #[Test]
    public function can_toggle_todo_completion(): void
    {
        $todo = new Todo();
        $todo->setTitle('Test Todo');
        $todo->setCompleted(false);

        $this->service->toggleCompletion($todo);
        $this->assertTrue($todo->isCompleted());

        $this->service->toggleCompletion($todo);
        $this->assertFalse($todo->isCompleted());
    }

    #[Test]
    public function title_is_required_when_creating(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Title cannot be empty');

        $this->service->createTodo('', null);
    }
}
```

### 1.6: Run All Unit Tests

```bash
bin/phpunit tests/Unit/
```

**Expected output:**
```
OK (6 tests, 11 assertions)
```

✅ **Phase 1 Complete!** Business logic is tested without database.

---

## Phase 2: Integration Tests (Database Layer)

**What you'll learn:**
- Test with real database (SQLite in-memory)
- Test persistence and relationships
- Repository patterns

### 2.1: Database Test Case Base

`tests/DatabaseTestCase.php`:

```php
<?php

namespace App\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DatabaseTestCase extends KernelTestCase
{
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine.orm.entity_manager');

        $this->setupDatabase();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    protected function setupDatabase(): void
    {
        $metadataFactory = $this->entityManager->getMetadataFactory();
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);

        try {
            $schemaTool->dropSchema($metadataFactory->getAllMetadata());
        } catch (\Exception) {
            // Schema doesn't exist yet
        }

        $schemaTool->createSchema($metadataFactory->getAllMetadata());
    }

    protected function persist($entity): void
    {
        $this->entityManager->persist($entity);
    }

    protected function flush(): void
    {
        $this->entityManager->flush();
    }
}
```

### 2.2: User Entity

`src/Entity/User.php`:

```php
<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    /**
     * @var Collection<int, Todo>
     */
    #[ORM\OneToMany(targetEntity: Todo::class, mappedBy: 'user')]
    private Collection $todos;

    public function __construct()
    {
        $this->todos = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return Collection<int, Todo>
     */
    public function getTodos(): Collection
    {
        return $this->todos;
    }

    public function addTodo(Todo $todo): static
    {
        if (!$this->todos->contains($todo)) {
            $this->todos->add($todo);
            $todo->setUser($this);
        }
        return $this;
    }

    public function removeTodo(Todo $todo): static
    {
        if ($this->todos->removeElement($todo)) {
            if ($todo->getUser() === $this) {
                $todo->setUser(null);
            }
        }
        return $this;
    }
}
```

### 2.3: Create Repository Class

`src/Repository/TodoRepository.php`:

```php
<?php

namespace App\Repository;

use App\Entity\Todo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TodoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Todo::class);
    }

    public function findAll(): array
    {
        return $this->findBy([], ['createdAt' => 'DESC']);
    }
}
```

Then reference it in your entity with the `repositoryClass` parameter:

```php
#[ORM\Entity(repositoryClass: \App\Repository\TodoRepository::class)]
class Todo
{
    // ... rest of entity
}
```

### 2.4: Repository Tests

`tests/Functional/Repository/TodoRepositoryTest.php`:

```php
<?php

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
    public function can_create_and_persist_todo(): void
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
    public function can_find_todo_by_id(): void
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
    public function can_find_all_todos(): void
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
    public function can_update_todo(): void
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
    public function can_delete_todo(): void
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
```

### 2.4: UserRepository (Auto-generated)

`src/Repository/UserRepository.php` is auto-generated when you use `make:entity User` with the entity attribute:

```php
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
{
    // ...
}
```

Symfony automatically creates the basic repository. No `make:repository` command needed in 8.1+.

### 2.5: Run Integration Tests

```bash
bin/phpunit tests/Functional/Repository/
```

**Expected output:**
```
OK (6 tests, 8 assertions)
```

✅ **Phase 2 Complete!** Database layer is tested.

---

## Phase 3: API Tests (Full Feature)

**What you'll learn:**
- Test HTTP endpoints
- Test request/response handling
- Full API workflow

### 3.1: API Controller

`src/Controller/Api/TodoController.php`:

```php
<?php

namespace App\Controller\Api;

use App\Entity\Todo;
use App\Repository\TodoRepository;
use App\Service\TodoService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/todos', name: 'api_todos_')]
class TodoController extends AbstractController
{
    public function __construct(
        private TodoRepository $repository,
        private TodoService $service,
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $todos = $this->repository->findAll();

        return $this->json([
            'success' => true,
            'data' => array_map(fn(Todo $todo) => [
                'id' => $todo->getId(),
                'title' => $todo->getTitle(),
                'description' => $todo->getDescription(),
                'completed' => $todo->isCompleted(),
                'createdAt' => $todo->getCreatedAt()->format('Y-m-d H:i:s'),
            ], $todos),
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['title'])) {
                return $this->json(['error' => 'Title is required'],
                    Response::HTTP_BAD_REQUEST);
            }

            $todo = $this->service->createTodo(
                $data['title'],
                $data['description'] ?? null
            );

            $this->entityManager->persist($todo);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'data' => [
                    'id' => $todo->getId(),
                    'title' => $todo->getTitle(),
                    'description' => $todo->getDescription(),
                ],
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()],
                Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Todo $todo): JsonResponse
    {
        return $this->json([
            'success' => true,
            'data' => [
                'id' => $todo->getId(),
                'title' => $todo->getTitle(),
                'description' => $todo->getDescription(),
                'completed' => $todo->isCompleted(),
                'createdAt' => $todo->getCreatedAt()->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, Todo $todo): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (isset($data['title'])) {
                $todo->setTitle($data['title']);
            }

            if (isset($data['description'])) {
                $todo->setDescription($data['description']);
            }

            if (isset($data['completed'])) {
                $todo->setCompleted($data['completed']);
            }

            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'data' => ['id' => $todo->getId(), 'title' => $todo->getTitle()],
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()],
                Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Todo $todo): JsonResponse
    {
        $this->entityManager->remove($todo);
        $this->entityManager->flush();

        return $this->json(['success' => true], Response::HTTP_NO_CONTENT);
    }
}
```

### 3.2: API Tests

`tests/Controller/Api/TodoControllerTest.php`:

```php
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
```

### 3.3: Run API Tests

```bash
bin/phpunit tests/Controller/Api/
```

**Expected output:**
```
OK (6 tests, 10+ assertions)
```

✅ **Phase 3 Complete!** API endpoints are tested.

---

## Phase 4: Smoke Tests (End-to-End)

**What you'll learn:**
- Complete workflows across all layers
- Full confidence in the system

### 4.1: Smoke Tests

`tests/Functional/SmokeTest.php`:

```php
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
        $this->testUser->setEmail('uday@uday.com.np');
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
```

### 4.2: Run Smoke Tests

```bash
bin/phpunit tests/Functional/SmokeTest.php
```

**Expected output:**
```
OK (3 tests, 15+ assertions)
```

✅ **Phase 4 Complete!** End-to-end workflows verified.

---

## Running Tests & Debugging

### Run All Tests

```bash
bin/phpunit
```

### Run by Category

```bash
# Unit tests only (fast)
bin/phpunit tests/Unit/

# Integration tests
bin/phpunit tests/Functional/Repository/

# API tests
bin/phpunit tests/Controller/Api/

# Smoke tests
bin/phpunit tests/Functional/SmokeTest.php
```

### Run Specific Test

```bash
bin/phpunit tests/Unit/TodoTest.php
```

### Debug Output

```bash
# Verbose
bin/phpunit -v tests/Unit/TodoTest.php

# Stop on first failure
bin/phpunit --stop-on-failure

# With code coverage
bin/phpunit --coverage-text
```

---

## Key Configuration Files

### phpunit.dist.xml

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         colors="true"
         failOnDeprecation="true"
         failOnNotice="true"
         failOnWarning="true"
         bootstrap="tests/bootstrap.php"
>
    <php>
        <ini name="display_errors" value="1" />
        <ini name="error_reporting" value="-1" />
        <server name="APP_ENV" value="test" force="true" />
        <server name="SHELL_VERBOSITY" value="-1" />
        <env name="APP_ENV" value="test"/>
        <env name="APP_SECRET" value=""/>
        <env name="DATABASE_URL" value="sqlite:///:memory:"/>
    </php>

    <testsuites>
        <testsuite name="Project Test Suite">
            <directory>tests</directory>
        </testsuite>
    </testsuites>

    <source>
        <include>
            <directory>src</directory>
        </include>
    </source>
</phpunit>
```

### .env (Development)

```bash
APP_ENV=dev
APP_SECRET=
DATABASE_URL="postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=16&charset=utf8"
```

### .env.test (Testing - Auto-used by phpunit)

In `phpunit.dist.xml`, `DATABASE_URL=sqlite:///:memory:` is used for blazing-fast tests.

---

## Key Lessons from Implementation

### What Works Well

1. **Doctrine ORM attributes** - `#[ORM\Entity]`, `#[ORM\Column]`, `#[ORM\ManyToOne]` provide clean, readable mappings
2. **DatabaseTestCase base** - Provides consistent setup/teardown for integration tests
3. **SQLite in-memory** - Tests run in milliseconds, no file I/O
4. **Autowiring** - Constructor injection automatically resolves dependencies
5. **Attributes over YAML** - Modern PHP 8+ style, type-safe

###  Common Pitfalls

1. **Forgetting `#[ORM\Entity]`** - Entity class won't be recognized by Doctrine
2. **Not mapping relationships on both sides** - `mappedBy` must match the property name
3. **Running tests without resetting database** - Use `setupDatabase()` to recreate schema
4. **Ignoring the RED phase** - Always verify tests fail before implementing
5. **`make:repository` doesn't exist in Symfony 8.1+** - Repositories must be created manually and referenced in entity via `repositoryClass` parameter

### TDD Workflow Checklist

- [ ] Write test (RED)
- [ ] Verify it fails for the right reason
- [ ] Write minimal code (GREEN)
- [ ] Verify all tests pass
- [ ] Refactor if needed
- [ ] Repeat for next feature

---

## Summary

1. **Phase 1: Unit Tests** → Business logic confidence
2. **Phase 2: Integration Tests** → Database confidence
3. **Phase 3: API Tests** → Endpoint confidence
4. **Phase 4: Smoke Tests** → System confidence

**Remember:** RED → GREEN → REFACTOR. Always watch the test fail first!
