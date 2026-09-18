# Symfony 8.1 Maker Bundle Commands

## Available Commands

```bash
bin/console list make
```

### Entity & Repository Management

| Command | Purpose | Output |
|---------|---------|--------|
| `make:entity` | Create/update Doctrine entities with ORM attributes | `src/Entity/ClassName.php` |
| ~~`make:repository`~~ | **REMOVED in 8.1** - Use entity's `repositoryClass` attribute instead | Manual creation required |

### Controller & HTTP

| Command | Purpose |
|---------|---------|
| `make:controller` | Create controller class |
| `make:crud` | Generate full CRUD for entity |
| `make:api-resource` | Create API Platform resource |

### Security & Auth

| Command | Purpose |
|---------|---------|
| `make:user` | Create security user entity |
| `make:auth` | Create authenticator |
| `make:security:custom` | Custom security authenticator |
| `make:security:form-login` | Form login authenticator |
| `make:registration-form` | User registration system |

### Testing

| Command | Purpose |
|---------|---------|
| `make:test` | Create test class (interactive) |
| `make:unit-test` | Create unit test |
| `make:functional-test` | Create functional test |

### Database & Migrations

| Command | Purpose |
|---------|---------|
| `make:migration` | Create migration from entity changes |

### Other

| Command | Purpose |
|---------|---------|
| `make:command` | Create console command |
| `make:form` | Create form class |
| `make:validator` | Create validator |
| `make:message` | Create messenger message |
| `make:subscriber` | Create event subscriber |
| `make:fixtures` | Create fixture class |

---

## How to Create Repositories in Symfony 8.1

### Step 1: Create Entity with Repository Reference

```bash
bin/console make:entity Todo
```

### Step 2: Create Repository Class Manually

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

    // Add custom methods here
    public function findRecent(int $limit = 10): array
    {
        return $this->findBy([], ['createdAt' => 'DESC'], $limit);
    }
}
```

### Step 3: Reference Repository in Entity

```php
#[ORM\Entity(repositoryClass: TodoRepository::class)]
class Todo
{
    // ... entity code
}
```

### Step 4: Inject into Services/Controllers

```php
public function __construct(private TodoRepository $repository)
{
}

public function doSomething()
{
    $todos = $this->repository->findRecent();
}
```

---

## Why `make:repository` Was Removed

In older Symfony versions, `make:repository` was a separate command. But:

1. **Auto-discovery**: Symfony 8.1+ discovers repositories automatically when referenced in entity
2. **Simpler**: Repository class is just a PHP file - no magic needed
3. **Flexibility**: Developers can create custom repositories without scaffolding
4. **Less bloat**: Reduces maker command complexity

The pattern now is:
- Create entity → Manually create repository → Reference in entity via `repositoryClass`

This is actually **simpler and more explicit** than before.

---

## Pro Tips

### Use `--no-interaction` to Skip Prompts

```bash
bin/console make:entity Todo --no-interaction
```

### Create Test with Entity

```bash
bin/console make:test TodoTest --unit
```

### List All Bundles & Services

```bash
bin/console debug:container
bin/console debug:autowiring
```

### Verify Your Setup

```bash
bin/console about          # Version info
bin/console debug:config doctrine  # Doctrine config
```
