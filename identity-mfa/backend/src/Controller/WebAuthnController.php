<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Webauthn\SymfonyBundle\Controller\AssertionController as SymfonyAssertionController;
use Webauthn\SymfonyBundle\Controller\AttestationController as SymfonyAttestationController;

#[Route('/api/webauthn', name: 'api_webauthn_')]
class WebAuthnController extends AbstractController
{
    #[Route('/options/register', name: 'options_register', methods: ['POST'])]
    public function registrationOptions(Request $request, SymfonyAttestationController $attestation): JsonResponse
    {
        // Delegate to bundle controller; wrapper for API path stability
        return new JsonResponse($attestation->options($request)->getContent(), 200, [], true);
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request, SymfonyAttestationController $attestation): JsonResponse
    {
        return new JsonResponse($attestation->result($request)->getContent(), 200, [], true);
    }

    #[Route('/options/login', name: 'options_login', methods: ['POST'])]
    public function authenticationOptions(Request $request, SymfonyAssertionController $assertion): JsonResponse
    {
        return new JsonResponse($assertion->options($request)->getContent(), 200, [], true);
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request, SymfonyAssertionController $assertion): JsonResponse
    {
        return new JsonResponse($assertion->result($request)->getContent(), 200, [], true);
    }
}


