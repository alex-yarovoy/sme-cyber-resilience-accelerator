<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\AuditLogger;
use App\Service\JwtTokenService;
use App\Service\RefreshTokenGenerator;
use App\Service\RateLimiterService;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\RiskScoringService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken;

#[Route('/api/auth', name: 'api_auth_')]
class AuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private JWTTokenManagerInterface $jwtManager,
        private TotpAuthenticatorInterface $totpAuthenticator,
        private JwtTokenService $jwtTokenService,
        private RefreshTokenGenerator $refreshTokenGenerator,
        private AuditLogger $auditLogger,
        private RateLimiterService $rateLimiterService,
        private ValidatorInterface $validator,
        private RiskScoringService $riskScoring
    ) {}

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data || !isset($data['email']) || !isset($data['password'])) {
            return $this->json(['error' => 'Email and password are required'], Response::HTTP_BAD_REQUEST);
        }

        // Rate limiting
        $clientIp = $request->getClientIp();
        if (!$this->rateLimiterService->isAllowed('login', $clientIp)) {
            $this->auditLogger->log('LOGIN_RATE_LIMITED', null, $clientIp, $request->headers->get('User-Agent'));
            return $this->json(['error' => 'Too many login attempts'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        try {
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
            
            if (!$user || !$user->isActive()) {
                throw new UserNotFoundException('Invalid credentials');
            }

            if (!$this->passwordHasher->isPasswordValid($user, $data['password'])) {
                throw new BadCredentialsException('Invalid credentials');
            }

            // Update last login
            $user->setLastLoginAt(new \DateTime());
            $this->entityManager->flush();

            // Risk-based step-up
            $risk = $this->riskScoring->score(
                $user,
                (string) $clientIp,
                $request->headers->get('User-Agent'),
                []
            );

            // Generate JWT tokens
            $accessToken = $this->jwtManager->create($user);
            $refreshToken = $this->refreshTokenGenerator->createForUser($user);

            // Log successful login
            $this->auditLogger->log('LOGIN_SUCCESS', $user, $clientIp, $request->headers->get('User-Agent'));

            $response = [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type' => 'Bearer',
                'expires_in' => 900, // 15 minutes
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'roles' => $user->getRoles(),
                    'mfa_enabled' => $user->isMfaEnabled(),
                    'is_verified' => $user->isVerified()
                ]
            ];

            // Check if MFA is required
            if ($user->isMfaEnabled() || $this->riskScoring->needStepUp($risk)) {
                $response['mfa_required'] = true;
                $response['access_token'] = null; // Don't return access token until MFA is verified
                $response['token'] = $this->jwtTokenService->generateMfaToken($user);
                $response['risk_score'] = $risk;
            }

            return $this->json($response);

        } catch (UserNotFoundException|BadCredentialsException $e) {
            $this->auditLogger->log('LOGIN_FAILED', null, $clientIp, $request->headers->get('User-Agent'), [
                'email' => $data['email'],
                'error' => $e->getMessage()
            ]);
            
            return $this->json(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }
    }

    #[Route('/mfa/verify', name: 'mfa_verify', methods: ['POST'])]
    public function verifyMfa(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data || !isset($data['token']) || !isset($data['code'])) {
            return $this->json(['error' => 'Token and code are required'], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Decode and validate the temporary token
            $payload = $this->jwtTokenService->decodeToken($data['token']);
            $user = $this->entityManager->getRepository(User::class)->find($payload['user_id']);
            
            if (!$user || !$user->isActive()) {
                throw new UserNotFoundException('User not found');
            }

            // Rate limiting for MFA attempts
            if (!$this->rateLimiterService->isAllowed('mfa', $user->getId())) {
                $this->auditLogger->log('MFA_RATE_LIMITED', $user, $request->getClientIp(), $request->headers->get('User-Agent'));
                return $this->json(['error' => 'Too many MFA attempts'], Response::HTTP_TOO_MANY_REQUESTS);
            }

            // Verify TOTP code
            if ($this->totpAuthenticator->checkCode($user, $data['code'])) {
                // Generate final access token
                $accessToken = $this->jwtManager->create($user);
                $refreshToken = $this->refreshTokenGenerator->createForUser($user);

                $this->auditLogger->log('MFA_SUCCESS', $user, $request->getClientIp(), $request->headers->get('User-Agent'));

                return $this->json([
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'token_type' => 'Bearer',
                    'expires_in' => 900,
                    'user' => [
                        'id' => $user->getId(),
                        'email' => $user->getEmail(),
                        'roles' => $user->getRoles(),
                        'mfa_enabled' => $user->isMfaEnabled(),
                        'is_verified' => $user->isVerified()
                    ]
                ]);
            } else {
                $this->auditLogger->log('MFA_FAILED', $user, $request->getClientIp(), $request->headers->get('User-Agent'), [
                    'code_attempted' => $data['code']
                ]);
                
                return $this->json(['error' => 'Invalid MFA code'], Response::HTTP_UNAUTHORIZED);
            }

        } catch (\Exception $e) {
            $this->auditLogger->log('MFA_ERROR', null, $request->getClientIp(), $request->headers->get('User-Agent'), [
                'error' => $e->getMessage()
            ]);
            
            return $this->json(['error' => 'MFA verification failed'], Response::HTTP_UNAUTHORIZED);
        }
    }

    #[Route('/refresh', name: 'refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['refresh_token'])) {
            return $this->json(['error' => 'Refresh token is required'], Response::HTTP_BAD_REQUEST);
        }

        $stored = $this->entityManager->getRepository(RefreshToken::class)->findOneBy([
            'refreshToken' => $data['refresh_token'],
        ]);

        if (!$stored) {
            $this->auditLogger->log('TOKEN_REFRESH_FAILED', null, $request->getClientIp(), $request->headers->get('User-Agent'), [
                'error' => 'Unknown refresh token',
            ]);

            return $this->json(['error' => 'Invalid refresh token'], Response::HTTP_UNAUTHORIZED);
        }

        $valid = $stored->getValid();
        if (!$valid instanceof \DateTimeInterface) {
            $this->entityManager->remove($stored);
            $this->entityManager->flush();
            $this->auditLogger->log('TOKEN_REFRESH_FAILED', null, $request->getClientIp(), $request->headers->get('User-Agent'), [
                'error' => 'Malformed refresh token validity',
            ]);

            return $this->json(['error' => 'Invalid refresh token'], Response::HTTP_UNAUTHORIZED);
        }
        $validUntil = \DateTimeImmutable::createFromInterface($valid);
        if ($validUntil < new \DateTimeImmutable()) {
            $this->entityManager->remove($stored);
            $this->entityManager->flush();
            $this->auditLogger->log('TOKEN_REFRESH_FAILED', null, $request->getClientIp(), $request->headers->get('User-Agent'), [
                'error' => 'Expired refresh token',
            ]);

            return $this->json(['error' => 'Invalid refresh token'], Response::HTTP_UNAUTHORIZED);
        }

        $subject = method_exists($stored, 'getUserIdentifier') ? $stored->getUserIdentifier() : $stored->getUsername();
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $subject]);
        if (!$user || !$user->isActive()) {
            $this->auditLogger->log('TOKEN_REFRESH_FAILED', null, $request->getClientIp(), $request->headers->get('User-Agent'), [
                'error' => 'User not found for refresh token',
            ]);

            return $this->json(['error' => 'Invalid refresh token'], Response::HTTP_UNAUTHORIZED);
        }

        $this->entityManager->remove($stored);
        $this->entityManager->flush();

        $accessToken = $this->jwtManager->create($user);
        $refreshToken = $this->refreshTokenGenerator->createForUser($user);

        $this->auditLogger->log('TOKEN_REFRESHED', $user, $request->getClientIp(), $request->headers->get('User-Agent'));

        return $this->json([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => 900,
        ]);
    }

    #[Route('/logout', name: 'logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        if ($user) {
            $this->auditLogger->log('LOGOUT', $user, $request->getClientIp(), $request->headers->get('User-Agent'));
        }

        return $this->json(['message' => 'Successfully logged out']);
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'mfa_enabled' => $user->isMfaEnabled(),
            'is_verified' => $user->isVerified(),
            'created_at' => $user->getCreatedAt(),
            'last_login_at' => $user->getLastLoginAt()
        ]);
    }
}
