<?php

namespace App\Service;

use App\Entity\Todo;

final class TodoService
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

    public function toggleCompletion(Todo $todo): Todo
    {
        $todo->setCompleted(!$todo->isCompleted());

        return $todo;
    }

    public function updateTodo(Todo $todo, string $title, ?string $description = null): Todo
    {
        if (empty($title)) {
            throw new \InvalidArgumentException('Title cannot be empty');
        }

        $todo->setTitle(trim($title));
        $todo->setDescription($description);

        return $todo;
    }
}
