# Redis Session Configuration with TDD

This document uses Test-Driven Development (TDD) to configure Redis session storage in Symfony 8.1. Follow the RED -> GREEN -> REFACTOR cycle for each step.

---

## Prerequisites

### Step 0: Install Predis Composer Package

Install Predis for Redis client support:

```bash
composer require predis/predis
```

Verify installation:

```bash
composer show predis/predis
```

Expected output:
```
name     : predis/predis
versions : * 2.2.x
type     : library
```

---

## Start Redis Server

### Using Docker

```bash
docker run -d --name redis \
  -p 6379:6379 \
  redis:7-alpine
```

Verify Redis is running:

```bash
docker exec redis redis-cli ping
```

Expected output:
```
PONG
```

---

## Environment Variables Setup

Create or update `.env` with Redis DSN:

```bash
###> Redis ###
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DB=0
REDIS_URL=redis://${REDIS_HOST}:${REDIS_PORT}/${REDIS_DB}
###< Redis ###
```

For production with authentication:

```bash
###> Redis (with password) ###
REDIS_HOST=redis-prod.example.com
REDIS_PORT=6379
REDIS_PASSWORD=your-secure-password
REDIS_DB=0
REDIS_URL=redis://:${REDIS_PASSWORD}@${REDIS_HOST}:${REDIS_PORT}/${REDIS_DB}
###< Redis ###
```

For testing (`.env.test`):

```bash
###> Redis (Testing) ###
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DB=1
REDIS_URL=redis://${REDIS_HOST}:${REDIS_PORT}/${REDIS_DB}
###< Redis ###
```

---

## Important: Session Configuration in Symfony

When configuring sessions in Symfony 8.1+, understand that:

- Session handlers auto-wire through DependencyInjection
- `config/packages/framework.yaml` controls session behavior
- Tests need session configuration in `config/packages/test/framework.yaml`
- Environment variables control which handler is active

Best practice: Test with Redis handler before using in production.

---

## TDD Cycle: Phase 1 - Session Service

### Step 1: RED - Write Failing Test

Create a test that verifies Redis session configuration:

`tests/Unit/Session/RedisSessionConfigTest.php`:

```php
<?php

namespace App\Tests\Unit\Session;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RedisSessionConfigTest extends TestCase
{
    #[Test]
    public function redis_dsn_format_is_valid(): void
    {
        // DSN format should be: redis://host:port/db or redis://password@host:port/db
        $dsn = $_ENV['REDIS_URL'] ?? '';

        $this->assertStringStartsWith('redis://', $dsn);
        $this->assertNotEmpty($dsn);
    }

    #[Test]
    public function session_prefix_is_configurable(): void
    {
        $prefix = 'symfony_session:';

        $this->assertStringEndsWith(':', $prefix);
        $this->assertNotEmpty($prefix);
    }

    #[Test]
    public function session_ttl_is_valid(): void
    {
        $ttl = 86400; // 24 hours

        $this->assertGreaterThan(0, $ttl);
        $this->assertEquals(86400, $ttl);
    }
}
```

### Run Test - Expect SUCCESS

```bash
bin/phpunit tests/Unit/Session/RedisSessionConfigTest.php
```

Expected output:
```
OK (3 tests, 6 assertions)
```

---

### Step 2: RED - Write Functional Test

Create a test that verifies Redis connection:

`tests/Functional/Session/RedisConnectionTest.php`:

```php
<?php

namespace App\Tests\Functional\Session;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RedisConnectionTest extends KernelTestCase
{
    #[Test]
    public function redis_url_is_configured(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        $redisUrl = $_ENV['REDIS_URL'];

        $this->assertNotEmpty($redisUrl);
        $this->assertStringStartsWith('redis://', $redisUrl);
    }

    #[Test]
    public function redis_dsn_format_is_valid(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        $redisDsn = $_ENV['REDIS_URL'];

        $this->assertStringStartsWith('redis://', $redisDsn);
        $this->assertStringContainsString(':', $redisDsn);
    }

    #[Test]
    public function framework_config_uses_redis_handler(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        // Check if Redis DSN is configured
        $redisUrl = $_ENV['REDIS_URL'];

        $this->assertNotEmpty($redisUrl);
        $this->assertStringStartsWith('redis://', $redisUrl);
    }
}
```

### Run Test - Expect FAILURE

```bash
bin/phpunit tests/Functional/Session/RedisConnectionTest.php
```

Expected error:
```
undefined environment variable "REDIS_URL"
```

---

## Configuration: Phase 2 - Framework Setup

### Step 3: GREEN - Configure Framework Session Handler

Update `config/packages/framework.yaml`:

```yaml
framework:
    session:
        handler_id: session.handler.redis
        cookie_secure: auto
        cookie_httponly: true
        cookie_samesite: lax
        gc_maxlifetime: 86400
```

Add Redis session handler configuration in `config/services.yaml`:

Using Predis with REDIS_URL DSN (Recommended):

```yaml
services:
    session.handler.redis:
        class: Symfony\Component\HttpFoundation\Session\Storage\Handler\RedisSessionHandler
        arguments:
            - '@redis.session'
            - prefix: 'symfony_session:'

    redis.session:
        class: Predis\Client
        arguments:
            - '%env(REDIS_URL)%'
```

Important: Predis does NOT support the `locking` option. If you need session locking, either use the native Redis extension or implement a custom locking mechanism. See fix-redis.md for details.

Or if using native Redis extension (with locking support):

```yaml
services:
    session.handler.redis:
        class: Symfony\Component\HttpFoundation\Session\Storage\Handler\RedisSessionHandler
        arguments:
            - '@redis.session'
            - prefix: 'symfony_session:'
              locking: true
              lock_prefix: 'symfony_lock:'
              lock_ttl: 10

    redis.session:
        class: Redis
        calls:
            - connect:
                - '%env(REDIS_HOST)%'
                - '%env(int:REDIS_PORT)%'
            - select:
                - '%env(int:REDIS_DB)%'
```

Note: Locking options are only supported with native Redis extension, not with Predis.

For more control with separate variables, use:

```yaml
services:
    session.handler.redis:
        class: Symfony\Component\HttpFoundation\Session\Storage\Handler\RedisSessionHandler
        arguments:
            - '@redis.session'
            - prefix: 'symfony_session:'
              locking: true

    redis.session:
        class: Predis\Client
        arguments:
            -
                scheme: redis
                host: '%env(REDIS_HOST)%'
                port: '%env(int:REDIS_PORT)%'
                database: '%env(int:REDIS_DB)%'
```

### Run Test - Expect SUCCESS

```bash
bin/phpunit tests/Functional/Session/RedisConnectionTest.php
```

Expected output:
```
OK (3 tests, 5 assertions)
```

---

## Test Configuration: Phase 3 - Test Environment

### Step 4: RED - Write Test Environment Test

Create a test that verifies test environment uses Redis:

`tests/Functional/Session/SessionStorageTest.php`:

```php
<?php

namespace App\Tests\Functional\Session;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Session\Session;

class SessionStorageTest extends KernelTestCase
{
    #[Test]
    public function session_can_be_created(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        $session = new Session();
        $session->start();
        $this->assertInstanceOf(Session::class, $session);
    }

    #[Test]
    public function session_data_can_be_stored(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        $session = new Session();
       

        $session->set('test_key', 'test_value');

        $this->assertEquals('test_value', $session->get('test_key'));
    }

    #[Test]
    public function session_data_persists(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        $session = new Session();
        

        $session->set('user_id', 123);
        $session->set('username', 'testuser');

        $this->assertEquals(123, $session->get('user_id'));
        $this->assertEquals('testuser', $session->get('username'));
    }
}
```

### Run Test - Expect FAILURE

```bash
bin/phpunit tests/Functional/Session/SessionStorageTest.php
```

Expected error:
```
Session is not started
```

---

### Step 5: GREEN - Configure Test Session Handler

Update or create `config/packages/test/framework.yaml`:

```yaml
framework:
    session:
        storage_factory_id: session.storage.factory.mock_file
        handler_id: session.handler.native_file
        cookie_httponly: true
```

Or for Redis in tests using REDIS_URL DSN:

```yaml
framework:
    session:
        handler_id: session.handler.redis
        storage_factory_id: session.storage.factory.native
        cookie_httponly: true

services:
    redis.session:
        class: Predis\Client
        arguments:
            - '%env(REDIS_URL)%'
```

For testing with different database (override REDIS_DB in .env.test to use DB 1):

```bash
# .env.test
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DB=1
REDIS_URL=redis://${REDIS_HOST}:${REDIS_PORT}/${REDIS_DB}
```

### Run Test - Expect SUCCESS

```bash
bin/phpunit tests/Functional/Session/SessionStorageTest.php
```

Expected output:
```
OK (3 tests, 4 assertions)
```

---

## Controller Test: Phase 4 - HTTP Session Handling

### Step 6: RED - Write HTTP Session Test

`tests/Functional/Controller/SessionControllerTest.php`:

```php
<?php

namespace App\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SessionControllerTest extends WebTestCase
{
    #[Test]
    public function session_data_can_be_set(): void
    {
        $client = static::createClient();

        $client->request('GET', '/session-test/set/test_key/test_value');

        $this->assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);
        $this->assertEquals('test_key', $response['data']['key']);
        $this->assertEquals('test_value', $response['data']['value']);
    }

    #[Test]
    public function session_data_can_be_retrieved(): void
    {
        $client = static::createClient();

        // Set session data
        $client->request('GET', '/session-test/set/username/testuser');
        $this->assertResponseIsSuccessful();

        // Retrieve session data
        $client->request('GET', '/session-test/get/username');
        $this->assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);
        $this->assertEquals('username', $response['key']);
        $this->assertEquals('testuser', $response['value']);
    }

    #[Test]
    public function session_all_returns_session_data(): void
    {
        $client = static::createClient();

        // Set multiple session values
        $client->request('GET', '/session-test/set/key1/value1');
        $client->request('GET', '/session-test/set/key2/value2');

        // Get all session data
        $client->request('GET', '/session-test/all');
        $this->assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);
        $this->assertIsArray($response['data']);
    }
}
```

### Run Test - Expect FAILURE

```bash
bin/phpunit tests/Functional/Controller/SessionControllerTest.php
```

Expected errors:
```
- 404 Not Found on / route (doesn't exist)
- Service "session" not found in test container
```

Important: These tests fail because they try to:
1. Access `/` route which doesn't exist
2. Directly access the `session` service which isn't always available in test container

The solution is to test through actual controller endpoints instead.

---

### Step 7: GREEN - Create Session Test Controller

Create a simple controller for testing:

`src/Controller/SessionTestController.php`:

```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/session-test')]
class SessionTestController extends AbstractController
{
    #[Route('/set/{key}/{value}', name: 'session_set', methods: ['GET'])]
    public function setSessionData(
        SessionInterface $session,
        string $key,
        string $value
    ): Response {
        $session->set($key, $value);

        return $this->json([
            'success' => true,
            'message' => "Session key '{$key}' set to '{$value}'",
            'data' => [
                'key' => $key,
                'value' => $value,
            ],
        ]);
    }

    #[Route('/get/{key}', name: 'session_get', methods: ['GET'])]
    public function getSessionData(
        SessionInterface $session,
        string $key
    ): Response {
        $value = $session->get($key);

        return $this->json([
            'success' => true,
            'key' => $key,
            'value' => $value,
        ]);
    }

    #[Route('/all', name: 'session_all', methods: ['GET'])]
    public function getAllSessionData(SessionInterface $session): Response
    {
        return $this->json([
            'success' => true,
            'data' => $session->all(),
        ]);
    }

    #[Route('/destroy', name: 'session_destroy', methods: ['GET'])]
    public function destroySession(SessionInterface $session): Response
    {
        $session->invalidate();

        return $this->json([
            'success' => true,
            'message' => 'Session destroyed',
        ]);
    }
}
```

### Run Test - Expect SUCCESS

```bash
bin/phpunit tests/Functional/Controller/SessionControllerTest.php
```

Expected output:
```
OK (3 tests, 9 assertions)
```

Important Notes on Testing HTTP Sessions:

1. **Use actual controller endpoints instead of direct service access**: Test container doesn't guarantee session service availability
2. **Each client creates independent session**: Use same client instance or cookies to persist session across requests
3. **Verify JSON responses**: Check `success` flag and response structure, not just HTTP status
4. **Test through public API**: Makes tests more reliable and closer to real usage

### Testing with curl

After the controller is created, test the session endpoints using curl:

```bash
# Set a session value
curl http://tdd-sf8-fresh.docker/session-test/set/user_id/42

# Expected response:
# {
#   "success": true,
#   "message": "Session key 'user_id' set to '42'",
#   "data": {
#     "key": "user_id",
#     "value": "42"
#   }
# }

# Get the session value (must use same session via cookies)
curl -b cookies.txt -c cookies.txt http://tdd-sf8-fresh.docker/session-test/set/username/testuser
curl -b cookies.txt http://tdd-sf8-fresh.docker/session-test/get/username

# Expected response:
# {
#   "success": true,
#   "key": "username",
#   "value": "testuser"
# }

# Get all session data
curl -b cookies.txt http://tdd-sf8-fresh.docker/session-test/all

# Expected response:
# {
#   "success": true,
#   "data": {
#     "user_id": "42",
#     "username": "testuser"
#   }
# }

# Destroy session
curl -b cookies.txt http://tdd-sf8-fresh.docker/session-test/destroy

# Expected response:
# {
#   "success": true,
#   "message": "Session destroyed"
# }
```

Important: Use `-b cookies.txt -c cookies.txt` to maintain session cookies across requests, otherwise each request creates a new session.

---

## Integration Test: Phase 5 - Complete Session Workflow

### Step 8: RED - Write Complete Workflow Test

`tests/Functional/Session/CompleteWorkflowTest.php`:

```php
<?php

namespace App\Tests\Functional\Session;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CompleteWorkflowTest extends WebTestCase
{
    #[Test]
    public function complete_session_lifecycle(): void
    {
        $client = static::createClient();

        // Set session data
        $client->request('GET', '/session-test/set/user_id/42');
        $this->assertResponseIsSuccessful();

        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($content['success']);
        $this->assertEquals('42', $content['data']['value']);
    }

    #[Test]
    public function session_persists_across_multiple_requests(): void
    {
        $client = static::createClient();

        // Set data in first request
        $client->request('GET', '/session-test/set/username/testuser');
        $this->assertResponseIsSuccessful();

        // Retrieve in second request
        $client->request('GET', '/session-test/get/username');
        $this->assertResponseIsSuccessful();

        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('testuser', $content['value']);
    }

    #[Test]
    public function multiple_session_values_can_coexist(): void
    {
        $client = static::createClient();

        // Set multiple values
        $client->request('GET', '/session-test/set/key1/value1');
        $client->request('GET', '/session-test/set/key2/value2');
        $client->request('GET', '/session-test/set/key3/value3');

        // Get all
        $client->request('GET', '/session-test/all');
        $this->assertResponseIsSuccessful();

        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(3, $content['data']);
    }

    #[Test]
    public function session_can_be_destroyed(): void
    {
        $client = static::createClient();

        // Set data
        $client->request('GET', '/session-test/set/data/value');

        // Destroy session
        $client->request('GET', '/session-test/destroy');
        $this->assertResponseIsSuccessful();

        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($content['success']);
        $this->assertStringContainsString('destroyed', $content['message']);
    }
}
```

### Run Test - Expect SUCCESS

```bash
bin/phpunit tests/Functional/Session/CompleteWorkflowTest.php
```

Expected output:
```
OK (4 tests, 8 assertions)
```

---

## Verification Commands

### Check Redis Session Data

View sessions stored in Redis:

```bash
docker exec redis redis-cli
```

Then in the Redis CLI, use these commands:

```bash
# List all keys
KEYS *

# List all keys with prefix filter
KEYS symfony_session:*

# Get session count
DBSIZE

# Get specific session by key
GET symfony_session:abc123def456

# Get all session keys with details
KEYS symfony_session:* | xargs redis-cli GET

# Check if key exists
EXISTS symfony_session:abc123def456

# Get key type
TYPE symfony_session:abc123def456

# Get key size (in bytes)
STRLEN symfony_session:abc123def456

# Clear specific session
DEL symfony_session:abc123def456

# Clear all sessions
FLUSHDB

# Get memory used by sessions
INFO memory
```

### Quick Check from Command Line

Without entering redis-cli interactive mode:

```bash
# List all session keys
docker exec redis redis-cli KEYS "symfony_session:*"

# Get specific session value
docker exec redis redis-cli GET symfony_session:abc123def456

# Count total sessions
docker exec redis redis-cli KEYS "symfony_session:*" | wc -l

# Get total number of keys in Redis
docker exec redis redis-cli DBSIZE

# Delete specific session
docker exec redis redis-cli DEL symfony_session:abc123def456

# Clear all data
docker exec redis redis-cli FLUSHDB
```

### Monitor Redis in Real-time

```bash
docker exec redis redis-cli MONITOR
```

Then make requests and see Redis commands being executed.

### Check Session TTL

```bash
docker exec redis redis-cli
TTL symfony_session:abc123def456
```

Returns seconds until session expires.

---

## Configuration Options

### Session TTL (Time To Live)

`config/packages/framework.yaml`:

```yaml
framework:
    session:
        gc_maxlifetime: 86400  # 24 hours in seconds
```

### Session Prefix

`config/services.yaml`:

```yaml
services:
    session.handler.redis:
        class: Symfony\Component\HttpFoundation\Session\Storage\Handler\RedisSessionHandler
        arguments:
            - '@redis.session'
            - prefix: 'myapp_session:'
```

### Multiple Redis Databases

```bash
# Development: DB 0
REDIS_DB=0

# Testing: DB 1
REDIS_DB=1

# Staging: DB 2
REDIS_DB=2
```

### Redis Clustering (Optional)

For multiple Redis servers, use environment variable for the DSN list:

`.env`:
```bash
REDIS_CLUSTER_NODES=redis://redis-node-1:6379/0,redis://redis-node-2:6379/0,redis://redis-node-3:6379/0
```

`config/services.yaml`:
```yaml
services:
    redis.session:
        class: Predis\Client
        arguments:
            - '%env(json:REDIS_CLUSTER_NODES)%'
```

Or simpler, use single REDIS_URL for primary node:

```yaml
services:
    redis.session:
        class: Predis\Client
        arguments:
            - '%env(REDIS_URL)%'
```

---

## Troubleshooting

| Problem | Solution |
|---------|----------|
| Connection refused | Ensure Redis running: `docker ps \| grep redis` |
| Session not persisting | Check Redis DSN in .env file |
| Session TTL not respected | Verify gc_maxlifetime in framework.yaml |
| Data lost after restart | Enable Redis persistence in config |
| Wrong Redis database | Check REDIS_DB in .env matches session config |
| Sessions mixed between apps | Use unique prefix for each application |

---

## Performance Optimization

### Session Locking (Native Redis Extension Only)

Enable locking to prevent session race conditions (only with native Redis extension):

```yaml
services:
    session.handler.redis:
        arguments:
            - '@redis.session'
            - locking: true
              lock_prefix: 'symfony_lock:'
              lock_ttl: 10
```

For Predis client, locking is not supported. See fix-redis.md for alternatives.

### Session Serialization

Configure serialization method:

```yaml
services:
    session.handler.redis:
        arguments:
            - '@redis.session'
            - serialize: 'json'
```

### Compression

For large sessions:

```yaml
services:
    session.handler.redis:
        arguments:
            - '@redis.session'
            - compression: 'gzip'
```

---

## Production Checklist

- [ ] Redis password configured in .env
- [ ] Redis persistence enabled
- [ ] Session TTL set appropriately
- [ ] Redis monitoring configured
- [ ] Backup strategy in place
- [ ] Session prefix unique per application
- [ ] Redis replication configured (if needed)
- [ ] TTL monitoring alerts setup
- [ ] Session encryption enabled (optional)
- [ ] Test failover scenarios

---

## Run All Tests

### Unit Tests

```bash
bin/phpunit tests/Unit/Session/
```

### Functional Tests

```bash
bin/phpunit tests/Functional/Session/
```

### Controller Tests

```bash
bin/phpunit tests/Functional/Controller/SessionControllerTest.php
```

### All Session Tests

```bash
bin/phpunit tests/ -k Session
```

---

## Summary

Redis session configuration with TDD provides:

1. Unit Tests - Configuration validation
2. Functional Tests - Redis connectivity
3. Storage Tests - Session persistence
4. HTTP Tests - Web session handling
5. Workflow Tests - Complete lifecycle
6. Integration Tests - Multi-request scenarios

Key learnings:

- Redis requires proper handler configuration in Symfony
- Test and production environments may use different Redis databases
- Session TTL must be managed in both config and Redis
- Locking prevents race conditions in high-concurrency scenarios
- Monitoring Redis helps identify session issues early

Next steps:

- Add session analytics
- Implement session invalidation on logout
- Add session encryption for sensitive data
- Configure Redis cluster for high availability
- Set up Redis monitoring and alerts

Remember: RED -> GREEN -> REFACTOR. Always verify tests fail before implementing!
