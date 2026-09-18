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
