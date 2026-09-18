<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\Todo;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TodoTest extends TestCase
{
    #[Test]
    public function canCreateTodoWithTitle(): void
    {
        $todo = new Todo();
        $todo->setTitle('Buy groceries');

        $this->assertEquals('Buy groceries', $todo->getTitle());
    }

    #[Test]
    public function canSetDescription(): void
    {
        $todo = new Todo();
        $todo->setTitle('Buy groceries');
        $todo->setDescription('Milk, eggs, bread');

        $this->assertEquals('Milk, eggs, bread', $todo->getDescription());
    }

    #[Test]
    public function hasCreatedAtTimestamp(): void
    {
        $todo = new Todo();

        $this->assertNotNull($todo->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $todo->getCreatedAt());
    }
}
