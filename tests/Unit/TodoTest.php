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
