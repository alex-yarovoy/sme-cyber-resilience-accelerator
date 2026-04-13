<?php

namespace App\Service;

use App\Entity\User;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken;
use Symfony\Component\Security\Core\User\UserInterface;

class RefreshTokenGenerator
{
    public function __construct(private EntityManagerInterface $entityManager, private int $ttl = 604800)
    {
    }

    public function createForUser(UserInterface $user): string
    {
        $refreshToken = new RefreshToken();
        $refreshToken->setUsername(method_exists($user, 'getUserIdentifier') ? $user->getUserIdentifier() : '');
        $refreshToken->setRefreshToken(bin2hex(random_bytes(40)));
        $refreshToken->setValid((new DateTimeImmutable())->add(new DateInterval('PT' . $this->ttl . 'S')));

        $this->entityManager->persist($refreshToken);
        $this->entityManager->flush();

        return $refreshToken->getRefreshToken();
    }
}


