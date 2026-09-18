<?php

namespace App\Controller\Api;

use App\Entity\Todo;
use App\Repository\TodoRepository;
use App\Service\TodoService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/todos', name: 'api_todos_')]
class TodoController extends AbstractController
{
    public function __construct(
        private TodoRepository $repository,
        private TodoService $service,
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $todos = $this->repository->findAll();
        $todos = array_map(function (Todo $todo): array {
            return [
                'id' => $todo->getId(),
                'title' => $todo->getTitle(),
                'description' => $todo->getDescription(),
                'completed' => $todo->isCompleted(),
                'createdAt' => $todo->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $todos);

        return $this->json([
            'success' => true,
            'data' => $todos,
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['title'])) {
                return $this->json(['error' => 'Title is required'],
                    Response::HTTP_BAD_REQUEST);
            }

            $todo = $this->service->createTodo(
                $data['title'],
                $data['description'] ?? null
            );

            $this->entityManager->persist($todo);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'data' => [
                    'id' => $todo->getId(),
                    'title' => $todo->getTitle(),
                    'description' => $todo->getDescription(),
                ],
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()],
                Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Todo $todo): JsonResponse
    {
        return $this->json([
            'success' => true,
            'data' => [
                'id' => $todo->getId(),
                'title' => $todo->getTitle(),
                'description' => $todo->getDescription(),
                'completed' => $todo->isCompleted(),
                'createdAt' => $todo->getCreatedAt()->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, Todo $todo): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (isset($data['title'])) {
                $todo->setTitle($data['title']);
            }

            if (isset($data['description'])) {
                $todo->setDescription($data['description']);
            }

            if (isset($data['completed'])) {
                $todo->setCompleted($data['completed']);
            }

            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'data' => ['id' => $todo->getId(), 'title' => $todo->getTitle()],
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()],
                Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Todo $todo): JsonResponse
    {
        $this->entityManager->remove($todo);
        $this->entityManager->flush();

        return $this->json(['success' => true], Response::HTTP_NO_CONTENT);
    }
}
