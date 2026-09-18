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
