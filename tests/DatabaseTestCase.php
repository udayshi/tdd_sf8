<?php

namespace App\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DatabaseTestCase extends KernelTestCase
{
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine.orm.entity_manager');

        $this->setupDatabase();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    protected function setupDatabase(): void
    {
        $metadataFactory = $this->entityManager->getMetadataFactory();
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);

        try {
            $schemaTool->dropSchema($metadataFactory->getAllMetadata());
        } catch (\Exception) {
            // Schema doesn't exist yet
        }

        $schemaTool->createSchema($metadataFactory->getAllMetadata());
    }

    protected function persist($entity): void
    {
        $this->entityManager->persist($entity);
    }

    protected function flush(): void
    {
        $this->entityManager->flush();
    }
}
