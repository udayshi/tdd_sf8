<?php

declare(strict_types=1);

namespace App\Tests\Functional\Command;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class WorkerMqCommandTest extends KernelTestCase
{
    #[Test]
    public function workerCommandExists(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:worker-mq');

        $this->assertNotNull($command);
        $this->assertEquals('app:worker-mq', $command->getName());
    }
}
