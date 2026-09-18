final <?php

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
}
