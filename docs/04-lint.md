# Linting and Type Checking Guide

This document walks you through configuring linting and static type checking for this Symfony project.

## Overview

We'll set up three complementary tools:

1. **PHPStan** - Static type analyzer (catches type errors without running code)
2. **PHP-CS-Fixer** - Code style formatter (enforces PSR-12/Symfony standard)
3. **Psalm** (optional) - Alternative/complementary type checker with stricter analysis

These tools work together to catch bugs early, enforce consistent style, and ensure type safety.

---

## 1. PHPStan (Static Type Checking)

PHPStan analyzes your code statically to find type errors, undefined methods, incorrect property access, and more—without running the code.

### Installation

```bash
composer require --dev phpstan/phpstan
```

### Configuration

Create `phpstan.neon` in the project root:

```neon
includes:
  - vendor/phpstan/phpstan-symfony/extension.neon
  - vendor/phpstan/phpstan-doctrine/extension.neon

parameters:
  level: 8
  paths:
    - src
  excludePaths:
    - migrations
    - tests
    
  reportUnmatchedIgnoredErrors: true
  treatPhpDocTypesAsCertain: true
```

**What each line does:**
- `includes`: Load Symfony and Doctrine rule sets (install these packages below)
- `level: 8`: Strictness level (0=loose, 9=strict). Start at 8 for Symfony projects.
- `paths`: Directories to analyze
- `excludePaths`: Skip migrations (they're auto-generated)
- `reportUnmatchedIgnoredErrors`: Fail if you ignore an error that no longer exists
- `treatPhpDocTypesAsCertain`: Trust `@param` and `@return` type hints

### Install Symfony/Doctrine Stubs

```bash
composer require --dev phpstan/phpstan-symfony phpstan/phpstan-doctrine
```

### Run PHPStan

```bash
vendor/bin/phpstan analyse
```

**Tip:** Add to `composer.json` scripts:

```json
"scripts": {
  "lint:types": "phpstan analyse"
}
```

Then run: `composer lint:types`

### Fixing Type Errors

PHPStan output shows file, line, and error:

```
 ------ -----------------------------------------------
  Line   src/Controller/TodoController.php
 ------ -----------------------------------------------
  42     Parameter $id of method TodoController::show() has invalid typehint type int|string.
 ------ -----------------------------------------------
```

**Common fixes:**

- Add type hints to function arguments: `public function show(int $id): Response`
- Add return types: `public function getData(): array`
- Use nullable types for optional values: `?int`, `?string`
- Import types at the top: `use Doctrine\Persistence\ObjectRepository;`

---

## 2. PHP-CS-Fixer (Code Style)

PHP-CS-Fixer automatically formats code to follow PSR-12 and Symfony coding standards.

### Installation

```bash
composer require --dev friendsofphp/php-cs-fixer
```
### Configuration
```php
<?php

$finder = (new PhpCsFixer\Finder())
    ->in('src')
    ->in('tests')
    ->in('config')
    ->exclude(['migrations', 'var'])
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'declare_strict_types' => true,  // ← Add this line
        'ordered_class_elements' => true,
        'phpdoc_order' => true,
        'phpdoc_summary' => false,
    ])
    ->setFinder($finder)
    ->setCacheFile('.php-cs-fixer.cache')
    ;


```

### Run PHP-CS-Fixer

```bash
# Dry run (show what would change):
vendor/bin/php-cs-fixer fix --dry-run

# Actually fix files:
vendor/bin/php-cs-fixer fix
```

**Add to `composer.json` scripts:**

```json
"scripts": {
  "lint:fix": "php-cs-fixer fix --allow-risky=yes",
  "lint:check": "php-cs-fixer fix --dry-run --allow-risky=yes"
}
```

Then run: `composer lint:check` or `composer lint:fix`

---

## 3. Psalm (Optional Alternative Type Checker)

Psalm is another static type analyzer, stricter than PHPStan in some ways. Use it if you want belt-and-suspenders type checking.

### Installation

```bash
composer require --dev vimeo/psalm
```

### Configuration

Create `psalm.xml` in the project root:

```xml
<?xml version="1.0"?>
<psalm
    errorLevel="5"
    resolveFromConfigFile="true"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xmlns="https://getpsalm.org/schema/config"
    xsi:schemaLocation="https://getpsalm.org/schema/config vendor/vimeo/psalm/config.xsd"
>
    <projectFiles>
        <directory name="src" />
        <directory name="tests" />
        <ignoreFiles>
            <directory name="vendor" />
            <directory name="migrations" />
        </ignoreFiles>
    </projectFiles>
</psalm>
```

### Run Psalm

```bash
vendor/bin/psalm
```

---

## 4. Putting It All Together

### Add Composer Scripts

Update `composer.json`:

```json
"scripts": {
  "lint": [
    "@lint:types",
    "@lint:check",
    "@lint:psalm"
  ],
  "lint:types": "phpstan analyse",
  "lint:check": "php-cs-fixer fix --dry-run",
  "lint:fix": "php-cs-fixer fix",
  "lint:psalm": "psalm"
}
```

Run all checks: `composer lint`

Fix code style: `composer lint:fix`

### Pre-Commit Hooks (Optional but Recommended)

Use Husky to run linters before each commit:

```bash
composer require --dev husky/husky lint-staged
npx husky install  # or: npx husky-init --yarn  (if using Yarn)
```

Add `.husky/pre-commit`:

```bash
#!/bin/sh
vendor/bin/php-cs-fixer fix
vendor/bin/phpstan analyse
```

Make it executable:

```bash
chmod +x .husky/pre-commit
```

Now linting runs automatically before commits.

---

## 5. CI/CD Integration

Add these checks to your CI pipeline (GitHub Actions, GitLab CI, etc.):

### GitHub Actions Example

Create `.github/workflows/lint.yml`:

```yaml
name: Lint

on: [push, pull_request]

jobs:
  lint:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          tools: composer
      - run: composer install
      - run: composer lint
```

---

## 6. IDE Integration

### VS Code

Install extensions:
- **PHP Intelephense** (Intellisense, type checking)
- **phpcs** (PHP CodeSniffer integration)

Add to `.vscode/settings.json`:

```json
{
  "intelephense.phpdoc.returnVoid": false,
  "php.validate.executablePath": "/path/to/php",
  "phpcs.standard": "PSR12"
}
```

### PhpStorm

- Built-in PHPStan and Psalm support
- Go to **Settings → PHP → Quality Tools** and configure paths
- Run **Code → Run Inspection** to check files

---

## 7. Workflow

### Daily Development

1. **Write code**
2. **Run tests**: `composer test`
3. **Check types**: `composer lint:types`
4. **Fix style**: `composer lint:fix`
5. **Commit**

### Before Pushing

```bash
composer lint
composer test
git push
```

### Ignoring Errors (Sparingly)

If PHPStan or Psalm reports a false positive, suppress it:

```php
/** @phpstan-ignore-next-line */
$value = someUnsafeFunction();

/** @psalm-suppress UndefinedClass */
$object = new ThirdPartyClass();
```

Use sparingly—suppressing errors hides real bugs.

---

## 8. Common PHPStan Errors and Solutions

### instanceof.alwaysTrue
**Error:** `Instanceof between App\Entity\Todo and App\Entity\Todo will always evaluate to true.`

**Cause:** Redundant type check after already-typed operations (e.g., `array_map` with specific type).

**Solution:** Type the callback parameter specifically:
```php
// ❌ Before
array_map(function (object $todo): array { ... }, $todos);

// ✅ After
array_map(function (Todo $todo): array { ... }, $todos);
```

### Type Mismatch in Function Calls
**Error:** `Parameter #1 $x of method Foo::bar() expects int, null given.`

**Solution:** Provide the correct type:
```php
// ❌ Before
$channel->basic_qos(null, 1, false);

// ✅ After
$channel->basic_qos(1, 1, false);
```

### Missing Generic Type Parameters
**Error:** `Class Foo extends generic class Bar but does not specify its types.`

**Solution:** Add docblock with generic type:
```php
/**
 * @extends ServiceEntityRepository<Todo>
 */
class TodoRepository extends ServiceEntityRepository { }
```

### Unused Methods/Properties
**Error:** `Method App\Kernel::getAllowedEnvs() is unused.`

**Solution:** Remove unused code or use it in the codebase. Trust PHPStan—if it says something's unused, it probably is.

---

## 9. Gradual Adoption

If your codebase has many type errors, adopt gradually:

### Phase 1: Code Style Only
```bash
composer require --dev friendsofphp/php-cs-fixer
composer lint:fix
```

### Phase 2: Add PHPStan (Low Level)
```bash
composer require --dev phpstan/phpstan phpstan/phpstan-symfony phpstan/phpstan-doctrine
# Set `level: 3` in phpstan.neon
composer lint:types
```

### Phase 3: Increase PHPStan Level
Once errors are fixed, increase level incrementally:
```
level: 3 → 4 → 5 → 6 → 7 → 8
```

### Phase 4: Add Psalm (Optional)
```bash
composer require --dev vimeo/psalm
composer lint:psalm
```

---

## 10. Troubleshooting

### PHPStan Reports False Positives

1. Check if it's actually unused: `vendor/bin/phpstan analyse --debug`
2. Suppress with `/** @phpstan-ignore-next-line */`
3. Update `phpstan.neon` to be less strict (lower `level`)

### PHP-CS-Fixer Conflicts with IDE

Disable auto-formatting in your IDE and only run `composer lint:fix` manually, or configure IDE to use the same rules.

### Slow Analysis

- Increase resources: `vendor/bin/phpstan analyse --memory-limit=-1`
- Use cache: `--cache-file=var/.phpstan.cache`
- Exclude slow directories

---

## Next Steps

1. Install PHPStan: `composer require --dev phpstan/phpstan phpstan/phpstan-symfony phpstan/phpstan-doctrine`
2. Create `phpstan.neon` (copy from Section 1)
3. Run `composer lint:types`
4. Fix errors, then commit
5. Add PHP-CS-Fixer, set up scripts, integrate into workflow


