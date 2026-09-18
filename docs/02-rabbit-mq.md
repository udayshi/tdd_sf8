# RabbitMQ Implementation with TDD

This document uses **Test-Driven Development (TDD)** to add RabbitMQ messaging to the existing project. Follow the RED → GREEN → REFACTOR cycle for each step.

---

## Prerequisites

### Step 0: Install RabbitMQ Composer Package

The project already has `php-amqplib` installed (check `composer.json`). If not, install it:

```bash
composer require php-amqplib/php-amqplib:^3.7
```

### Verify Installation

```bash
composer show php-amqplib/php-amqplib
```

**Expected output:**
```
name     : php-amqplib/php-amqplib
versions : * 3.7.0
type     : library
```

### Check if Already Installed

```bash
grep "php-amqplib" composer.json
```

If already installed, run:

```bash
composer install
```

---

## Important: Symfony Auto-Discovery & TDD

### Symfony 8.1+ Auto-Discovery Conflict

When using `KernelTestCase` (functional/integration tests), Symfony boots the full kernel and scans your `src/` directory. This means:

- Empty unit tests work fine (no kernel boot)
- Empty functional tests fail (kernel needs valid classes)
- Empty files in `src/` break container build
- All `#[AsCommand]` attributes get auto-wired at kernel boot

**TDD Impact:**
1. **Unit tests**: Can be written FIRST without implementation (true TDD)
2. **Functional tests**: Need implementation FIRST (breaks strict TDD)

**Why?** Symfony's `config/services.yaml` has:
```yaml
services:
  App\:
    resource: '../src/'
```

This auto-discovers ALL classes. If a file exists but is empty, Symfony's container build fails before any test runs.

### Best Practice for Symfony + TDD

```
Unit Tests (TDD-friendly):
  Write test -> RED (no implementation)
  Implement -> GREEN
  Refactor

Functional Tests (Implementation-first):
  Don't write test before implementing
  Implement code first
  Write test -> GREEN
  Refactor


## TDD Cycle: Phase 1 - Publisher Service

### Step 1: RED - Write Failing Test

First, create a test for the Publisher service **before** implementing it.

`tests/Unit/Message/PublisherTest.php`:

```php
<?php

namespace App\Tests\Unit\Message;

use App\Service\Message\Publisher;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PublisherTest extends TestCase
{
    private Publisher $publisher;

    protected function setUp(): void
    {
        // This will fail until Publisher exists
        $this->publisher = new Publisher('localhost', 5672, 'root', 'rootpass');
    }

    #[Test]
    public function can_instantiate_publisher(): void
    {
        $this->assertInstanceOf(Publisher::class, $this->publisher);
    }

    #[Test]
    public function can_publish_message(): void
    {
        // Won't fail on actual RabbitMQ connection - just test method exists
        $this->assertTrue(method_exists($this->publisher, 'publish'));
    }

    #[Test]
    public function publish_accepts_message_and_routing_key(): void
    {
        $reflection = new \ReflectionMethod(Publisher::class, 'publish');
        $params = $reflection->getParameters();

        $this->assertCount(2, $params);
        $this->assertEquals('message', $params[0]->getName());
        $this->assertEquals('routingKey', $params[1]->getName());
    }
}
```

### Run Test - Expect FAILURE

```bash
bin/phpunit tests/Unit/Message/PublisherTest.php
```

**Expected error:**
```
Error: Class "App\Service\Message\Publisher" not found
```

This is RED. Now implement to make it GREEN.

---

### Step 2: GREEN - Implement Publisher

Create the Publisher service to pass the test.

`src/Service/Message/Publisher.php`:

```php
<?php

namespace App\Service\Message;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class Publisher
{
    public function __construct(
        #[Autowire('%env(RABBITMQ_HOST)%')]
        private string $host,
        #[Autowire('%env(int:RABBITMQ_PORT)%')]
        private int $port,
        #[Autowire('%env(RABBITMQ_USER)%')]
        private string $user,
        #[Autowire('%env(RABBITMQ_PASSWORD)%')]
        private string $password,
    ) {
    }

    public function publish(string $message, string $routingKey = 'todo.created'): void
    {
        $connection = new AMQPStreamConnection(
            $this->host,
            $this->port,
            $this->user,
            $this->password
        );

        $channel = $connection->channel();

        // Declare durable exchange
        $channel->exchange_declare('todos.demo', 'direct', false, true, false);

        // Create AMQP message
        $msg = new AMQPMessage($message, ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);

        // Publish
        $channel->basic_publish($msg, 'todos.demo', $routingKey);

        $channel->close();
        $connection->close();
    }
}
```

### Run Test - Expect SUCCESS

```bash
bin/phpunit tests/Unit/Message/PublisherTest.php
```

**Expected output:**
```
OK (3 tests, 4 assertions)
```

---

### Step 3: REFACTOR - Improve Publisher (Optional)

Currently, the Publisher has a hard dependency on `AMQPStreamConnection`. For better testability, we can add a factory or interface. However, for this demo, it's sufficient.

**Refactoring Note:**
- The `#[Autowire]` attribute automatically injects env variables
- Error handling should be added in production (try-catch)
- Connection pool could be beneficial for multiple publishes

For now, keep it simple.

---

## Environment Setup: Phase 2 - Configuration

### Step 4: RED - Write Test for Environment Variables

`tests/Unit/Message/PublisherEnvironmentTest.php`:

```php
<?php

namespace App\Tests\Unit\Message;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PublisherEnvironmentTest extends KernelTestCase
{
    #[Test]
    public function env_variables_are_accessible(): void
    {
        $this->bootKernel();
        $container = static::getContainer();

        // Should be able to get parameters from env
        $host = $_ENV['RABBITMQ_HOST'] ?? 'localhost';
        $port = $_ENV['RABBITMQ_PORT'] ?? '5672';

        $this->assertNotEmpty($host);
        $this->assertNotEmpty($port);
    }

    #[Test]
    public function publisher_service_is_autowired(): void
    {
        $this->bootKernel();
        $container = static::getContainer();

        $publisher = $container->get('App\Service\Message\Publisher');

        $this->assertNotNull($publisher);
    }
}
```

### Run Test - Expect FAILURE

```bash
bin/phpunit tests/Unit/Message/PublisherEnvironmentTest.php
```

**Expected error:**
```
undefined environment variable "RABBITMQ_HOST"
```

---

### Step 5: GREEN - Add Environment Variables

Update `.env` file:

```bash
###> RabbitMQ ###
RABBITMQ_HOST=localhost
RABBITMQ_PORT=5672
RABBITMQ_USER=root
RABBITMQ_PASSWORD=rootpass
###< RabbitMQ ###
```

### Run Test - Expect SUCCESS

```bash
bin/phpunit tests/Unit/Message/PublisherEnvironmentTest.php
```

**Expected output:**
```
OK (2 tests, 3 assertions)
```

---

## Commands: Phase 3 - Publisher Command

### IMPORTANT: Implement Before Testing (Functional)

From this point on, we're writing **functional/integration tests** that use `KernelTestCase`. These tests **MUST have the implementation code FIRST**.

Why? Because when the kernel boots, Symfony scans `src/` and tries to instantiate all services. If the class file exists but is empty, container build fails.

**Do NOT create empty files before writing tests in functional phases.**

---

### Step 6: GREEN - Create Publisher Command (Before Test!)

**IMPLEMENT FIRST** - Create the command:

`src/Command/PublishMqCommand.php`:

```php
<?php

namespace App\Command;

use App\Service\Message\Publisher;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:publish-mq',
    description: 'Publish a message to RabbitMQ',
)]
class PublishMqCommand extends Command
{
    public function __construct(private Publisher $publisher)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('message', InputArgument::REQUIRED, 'Message to publish');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $message = $input->getArgument('message');

        try {
            $this->publisher->publish($message);
            $io->success("Message published: $message");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error("Failed to publish: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
```

### Step 7: RED - Write Test for Publisher Command (After Implementation)

`tests/Functional/Command/PublishMqCommandTest.php`:

```php
<?php

namespace App\Tests\Functional\Command;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class PublishMqCommandTest extends KernelTestCase
{
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $command = $application->find('app:publish-mq');
        $this->commandTester = new CommandTester($command);
    }

    #[Test]
    public function command_requires_message_argument(): void
    {
        $this->commandTester->execute([]);

        $this->assertNotEquals(0, $this->commandTester->getStatusCode());
    }

    #[Test]
    public function command_accepts_message_argument(): void
    {
        $this->commandTester->execute(['message' => 'Test message']);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Test message', $this->commandTester->getDisplay());
    }
}
```

### Run Test - Expect SUCCESS

```bash
bin/phpunit tests/Functional/Command/PublishMqCommandTest.php
```

**Expected output:**
```
OK (2 tests, 3 assertions)
```

---

### Step 8️⃣: REFACTOR - Add Error Handling (Optional)

The command already has try-catch. Consider adding:
- Validation for empty messages
- Logging published messages
- Connection timeout handling

For now, this is sufficient. ✅

---

## Integration: Phase 4 - Worker Command

### REMINDER: Implement Before Testing

Again, we're working with functional tests. **Create the worker command FIRST**, then write the test.

**Do NOT create an empty `src/Command/WorkerCommand.php` file.** This breaks Symfony's auto-discovery.

---

### Step 8: GREEN - Create Worker Command (Before Test!)

**IMPLEMENT FIRST** - Create the worker:



`src/Command/WorkerCommand.php`:

```php
<?php

namespace App\Command;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:worker-mq',
    description: 'Run the RabbitMQ worker',
)]
class WorkerCommand extends Command
{
    public function __construct(
        #[Autowire('%env(RABBITMQ_HOST)%')]
        private string $host,
        #[Autowire('%env(int:RABBITMQ_PORT)%')]
        private int $port,
        #[Autowire('%env(RABBITMQ_USER)%')]
        private string $user,
        #[Autowire('%env(RABBITMQ_PASSWORD)%')]
        private string $password,
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $io = new SymfonyStyle($input, $output);

        $connection = new AMQPStreamConnection(
            $this->host,
            $this->port,
            $this->user,
            $this->password
        );
        $channel = $connection->channel();

        // Declare exchange
        $channel->exchange_declare('todos.demo', 'direct', false, true, false);

        // Declare queue
        $channel->queue_declare('queue', false, false, false, false);

        // Bind queue to exchange
        $channel->queue_bind('queue', 'todos.demo');

        // QoS
        $channel->basic_qos(null, 1, null);

        $io->info('Worker listening for messages... Press Ctrl+C to stop.');

        $channel->basic_consume(
            'queue',
            'worker',
            false,
            false,
            false,
            false,
            function (AMQPMessage $msg) use ($io, $channel) {
                $io->writeln("Received: " . $msg->body);
                $channel->basic_ack($msg->delivery_info['delivery_tag']);
            }
        );

        while (count($channel->callbacks)) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();

        return Command::SUCCESS;
    }
}
```

### Step 9: RED - Write Test for Worker Command (After Implementation)

`tests/Functional/Command/WorkerMqCommandTest.php`:

```php
<?php

namespace App\Tests\Functional\Command;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class WorkerMqCommandTest extends KernelTestCase
{
    #[Test]
    public function worker_command_exists(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:worker-mq');

        $this->assertNotNull($command);
        $this->assertEquals('app:worker-mq', $command->getName());
    }
}
```

### Run Test - Expect SUCCESS

```bash
bin/phpunit tests/Functional/Command/WorkerMqCommandTest.php
```

**Expected output:**
```
OK (1 test, 2 assertions)
```

---

### Step 10: REFACTOR - Add Connection Error Handling (Optional)

Add try-catch for connection failures, timeout handling, etc.

For now, worker is functional.

---

## Demo: Phase 5 - End-to-End Test

### Step 11: Start RabbitMQ

```bash
docker run -d --name rabbitmq \
  -p 5672:5672 \
  -p 15672:15672 \
  -e RABBITMQ_DEFAULT_USER=root \
  -e RABBITMQ_DEFAULT_PASS=rootpass \
  rabbitmq:3-management-alpine
```

Access UI: `http://localhost:15672` (root/rootpass)

---

### Step 12: Run All Unit & Functional Tests

```bash
bin/phpunit tests/Unit/Message/
bin/phpunit tests/Functional/Command/
```

**Expected output:**
```
OK (6 tests, 10+ assertions)
```

---

### Step 13: Manual Integration Test - Publisher

**Terminal 1 - Publish Message:**

```bash
php bin/console app:publish-mq "Hello RabbitMQ with TDD!"
```

**Expected output:**
```
✓ Message published: Hello RabbitMQ with TDD!
```

---

### IMPORTANT: Queue Locking Issue

Before running the worker, you need to understand queue declarations:

- Publisher now declares ONLY the exchange
- Worker declares the queue with exclusive = true
- This prevents RESOURCE_LOCKED errors

---

### Step 14: Clean RabbitMQ (First Time Only)

Before first worker run, clean RabbitMQ state:

```bash
docker restart rabbitmq
```

This clears any stuck queue declarations.

---

### Step 15: Manual Integration Test - Worker

**Terminal 2 - Start Worker:**

```bash
php bin/console app:worker-mq
```

**Expected output:**
```
Worker listening for messages... Press Ctrl+C to stop.
```

**Terminal 1 - Send Messages:**

```bash
php bin/console app:publish-mq "Message 1"
php bin/console app:publish-mq "Message 2"
php bin/console app:publish-mq "Message 3"
```

**Terminal 2 - See Output:**

```
Received: Message 1
Received: Message 2
Received: Message 3
```

**Stop Worker:**
```bash
# Press Ctrl+C in Terminal 2
```

SUCCESS: Integration Test Passed!

---

## Understanding Environment Variables with `#[Autowire]`

### How It Works

When you use `#[Autowire('%env(VARIABLE_NAME)%')]` in Symfony 8.1+:

1. **Read from `.env`** - Symfony reads `RABBITMQ_HOST=localhost` from your `.env` file
2. **Resolve at runtime** - When the service is instantiated, it injects the value
3. **Type casting** - `%env(int:RABBITMQ_PORT)%` automatically converts string to integer
4. **No manual config needed** - No need to edit `services.yaml` or `config/packages`

### Type Casting Options

| Format | Type | Example |
|--------|------|---------|
| `%env(VAR)%` | string | `%env(RABBITMQ_HOST)%` → `"localhost"` |
| `%env(int:VAR)%` | integer | `%env(int:RABBITMQ_PORT)%` → `5672` |
| `%env(bool:VAR)%` | boolean | `%env(bool:DEBUG)%` → `true` |
| `%env(json:VAR)%` | array | `%env(json:CONFIG)%` → `[...]` |

---

## Debugging Environment Variables

### View Loaded Environment Variables

```bash
php bin/console debug:container --parameter=RABBITMQ_HOST
php bin/console debug:container --parameter=RABBITMQ_PORT
php bin/console debug:container --parameter=RABBITMQ_USER
php bin/console debug:container --parameter=RABBITMQ_PASSWORD
```

### Check Service Configuration

```bash
php bin/console debug:container App\\Service\\Message\\Publisher
```

### Create Debug Command (Optional)

`src/Command/DebugRabbitmqCommand.php`:

```php
<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(name: 'app:debug-rabbitmq')]
class DebugRabbitmqCommand extends Command
{
    public function __construct(
        #[Autowire('%env(RABBITMQ_HOST)%')]
        private string $host,
        #[Autowire('%env(int:RABBITMQ_PORT)%')]
        private int $port,
        #[Autowire('%env(RABBITMQ_USER)%')]
        private string $user,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("RABBITMQ_HOST: {$this->host}");
        $output->writeln("RABBITMQ_PORT: {$this->port}");
        $output->writeln("RABBITMQ_USER: {$this->user}");
        return Command::SUCCESS;
    }
}
```

Run it:

```bash
php bin/console app:debug-rabbitmq
```

---

## Verification

### Check RabbitMQ Status

```bash
docker exec rabbitmq rabbitmqctl status
```

### Monitor Exchanges

```bash
docker exec rabbitmq rabbitmqctl list_exchanges
```

### Monitor Queues

```bash
docker exec rabbitmq rabbitmqctl list_queues
```

---

## Troubleshooting

| Problem | Solution |
|---------|----------|
| Class not found | Run `composer install` after creating files |
| Connection refused | Ensure RabbitMQ is running: `docker ps \| grep rabbitmq` |
| Queue declaration fails | Delete queue: `docker exec rabbitmq rabbitmqctl delete_queue queue` |
| No messages consumed | Check worker is running and listening |
| Wrong exchange name | Verify publisher and worker use same exchange: `todos.demo` |
| Test fails with "undefined variable" | Check `.env` has RABBITMQ_* variables set |

---

## Key Learning: Message Persistence on Worker Crash

### The Problem

Initially:
- Worker crashes -> Unacknowledged messages lost
- Only new messages after restart picked up
- Exclusive queue auto-deletes on connection close

### The Solution

Changed from exclusive queue to durable queue:

```php
// Before (exclusive - messages lost on crash)
// queue_declare('queue', passive=false, durable=false, exclusive=true, ...)
$channel->queue_declare('queue', false, false, true, false);

// After (durable - messages persist)
// queue_declare('queue', passive=false, durable=true, exclusive=false, ...)
$channel->queue_declare('queue', false, true, false, false);
```

### How It Works Now

1. Publisher sends persistent message -> Saved to disk
2. Durable queue receives it -> Stored with queue
3. Worker processes message -> Gets first message via basic_consume
4. Worker crashes BEFORE ACK -> RabbitMQ knows message wasn't processed
5. Worker restarts -> Same message automatically re-delivered

### Key Changes Made

| Component | Before | After | Result |
|-----------|--------|-------|--------|
| Queue Type | Exclusive | Durable | Messages survive crashes |
| Auto-Delete | Yes | No | Queue persists |
| Routing Key | Default | Explicit (todo.created) | Clear message flow |

### Testing Persistence

```bash
# Terminal 1: Start worker
php bin/console app:worker-mq

# Terminal 2: Send message
php bin/console app:publish-mq "Test Crash"

# Terminal 1: See "Received: Test Crash" then kill worker
# (Ctrl+C before it finishes processing)

# Terminal 2: Verify queue still has message
docker exec rabbitmq rabbitmqctl list_queues
# Should show: queue    1 (unacknowledged message)

# Terminal 1: Restart worker
php bin/console app:worker-mq

# Terminal 1: Should automatically receive same message
# Received: Test Crash
```

### Manual ACK is Critical

Without manual acknowledgment, messages would be lost:

```php
// This is crucial - only delete message after processing succeeds
$channel->basic_ack($msg->delivery_info['delivery_tag']);
```

---

## Key Learning: Queue Locking in RabbitMQ

### The Step 14 Error You May Encounter

```
RESOURCE_LOCKED - cannot obtain exclusive access to locked queue 'queue'
```

### Why This Happens

1. **Publisher declares queue**: Opens connection, declares queue, closes connection
2. **Worker declares same queue**: Tries to declare, but queue still "locked" by previous declaration
3. **RabbitMQ rejects**: Two different connections can't both declare the same non-exclusive queue

### The Fix (Already Implemented)

We changed queue declaration to use **exclusive = true**:

```php
// Worker now declares queue as exclusive (only this connection can use it)
$channel->queue_declare('queue', false, false, true, false);
//                                                 ↑
//                                          exclusive = true
```

**Why this works:**
- Exclusive queue auto-deletes when worker disconnects
- Only current connection can use it
- No lock conflicts between runs

### Important: Clean RabbitMQ Between Runs

Always clean RabbitMQ state before running worker:

```bash
docker restart rabbitmq
```

This clears stuck queue declarations.

---

## Key Learning: Symfony Auto-Discovery vs Strict TDD

### The Problem You Encountered

When you ran bin/phpunit tests/Unit/Message/PublisherEnvironmentTest.php at Step 5, you got 2 errors:

```
Expected to find class "App\Command\WorkerCommand" in file
".../src/Command/WorkerCommand.php" but it was not found!
```

### Why This Happened

1. **Empty file existed**: `src/Command/WorkerCommand.php` was created but empty
2. **Symfony scanned it**: Auto-discovery found the file path
3. **Container build failed**: Symfony couldn't find the class definition
4. **Both tests failed**: Before any test code even ran!

### The Difference: Unit vs Functional Tests

| Test Type | Kernel Boot | Auto-Discovery | TDD Works? |
|-----------|------------|-----------------|-----------|
| Unit | No | No | Yes - Can write tests first |
| Functional | Yes | Yes | No - Need implementation first |

Unit Test Success:
```bash
bin/phpunit tests/Unit/Message/PublisherTest.php
# Works even if WorkerCommand is empty
# Kernel doesn't boot, no auto-discovery
```

Functional Test Failure:
```bash
bin/phpunit tests/Functional/Command/PublishMqCommandTest.php
# Fails if WorkerCommand is empty
# Kernel boots, auto-discovery scans src/
```

### Solution Applied in This Document

1. Unit Tests (Phases 1-2): Write test -> implement (strict TDD)
2. Functional Tests (Phases 3-4): Implement -> write test (reversed for Symfony)
3. Always fill implementation before testing with KernelTestCase

### The Updated TDD Approach for Symfony

```
Traditional TDD:
RED -> GREEN -> REFACTOR

Symfony TDD (with Functional Tests):
IMPLEMENT -> RED -> GREEN -> REFACTOR
```

Why? Symfony's kernel boot requires all #[AsCommand] classes to exist and be valid.

---

## TDD Checklist

Use this checklist for each feature:

- [ ] RED - Write test, verify it fails
- [ ] GREEN - Write minimal code to pass test
- [ ] REFACTOR - Improve code (optional if already clean)
- [ ] Run all tests - Ensure nothing broke
- [ ] Commit - Save working state to git

### All Phases Complete

- Phase 1: Publisher Service (Unit Test) - DONE
- Phase 2: Environment Configuration (Unit Test) - DONE
- Phase 3: Publisher Command (Functional Test) - DONE
- Phase 4: Worker Command (Functional Test) - DONE
- Phase 5: End-to-End Integration Test - DONE

---

## Summary

### TDD Approach Benefits

1. Tests ensure your code works before running manually
2. Failures are caught early (RED phase)
3. Code is simpler and more focused (GREEN phase)
4. Easy to refactor safely (REFACTOR phase)
5. Tests serve as documentation

### Important Symfony + TDD Learning

- **Unit Tests** follow strict TDD: RED → GREEN → REFACTOR
- **Functional Tests** require IMPLEMENT → RED → GREEN → REFACTOR
- **Never create empty command files** - breaks auto-discovery
- **Always implement before testing functional code** with `KernelTestCase`

### Why This Matters

Symfony 8.1+ auto-discovers services from `src/` directory. If a file exists but has no valid class, the kernel fails to boot before any test runs. This is a framework constraint, not a code problem.

**Solution:** Follow the phases in order, implementing before writing functional tests.

### Next Steps

- Add error handling and logging
- Create message handler classes
- Add persistence for failed messages
- Implement dead-letter queues
- Add metrics and monitoring

### Important Integration Lessons

1. Unit/Functional Tests don't catch infrastructure issues (Phase 1-4 passed)
2. Real integration tests reveal configuration problems (Phase 5 error)
3. Queue locking is a real RabbitMQ constraint you must handle
4. Always clean state between manual test runs
5. Message persistence requires durable queues + manual ACK + persistent messages
6. Crash recovery depends on not acking until processing succeeds

### Remember

- For Unit Tests: RED -> GREEN -> REFACTOR
- For Functional Tests: IMPLEMENT -> RED -> GREEN -> REFACTOR
- For Integration Tests: Watch for infrastructure constraints
