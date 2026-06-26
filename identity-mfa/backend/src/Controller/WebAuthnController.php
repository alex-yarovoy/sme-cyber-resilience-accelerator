<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/webauthn', name: 'api_webauthn_')]
class WebAuthnController extends AbstractController
{
    #[Route('/options/register', name: 'options_register', methods: ['POST'])]
    public function optionsRegister(): JsonResponse
    {
        return $this->notConfiguredResponse();
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(): JsonResponse
    {
        return $this->notConfiguredResponse();
    }

    #[Route('/options/login', name: 'options_login', methods: ['POST'])]
    public function optionsLogin(): JsonResponse
    {
        return $this->notConfiguredResponse();
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        return $this->notConfiguredResponse();
    }

    private function notConfiguredResponse(): JsonResponse
    {
        return $this->json([
            'error' => 'passkey_server_not_configured',
            'message' => 'Server-side WebAuthn credential storage is not enabled in this deployment profile.',
        ], Response::HTTP_SERVICE_UNAVAILABLE);
    }
}
