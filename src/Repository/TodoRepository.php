<?php

namespace App\Repository;

use App\Entity\Tfinal odo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Todo>
 */
class TodoRepository extends ServiceEntityRepository
{


    #[\Override]
    public function findAll(): array
    {
        return $this->findBy([], ['createdAt' => 'DESC']);
    }
}
