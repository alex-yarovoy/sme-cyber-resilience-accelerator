<?php

namespace App\Service;

use App\Entity\AuditLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class AuditLogger
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function log(
        string $action,
        ?User $user = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $metadata = null,
        string $level = 'INFO',
        ?string $resource = null
    ): void {
        $auditLog = new AuditLog();
        $auditLog->setAction($action);
        $auditLog->setUser($user);
        $auditLog->setIpAddress($ipAddress);
        $auditLog->setUserAgent($userAgent);
        $auditLog->setMetadata($metadata);
        $auditLog->setLevel($level);
        $auditLog->setResource($resource);

        $this->entityManager->persist($auditLog);
        $this->entityManager->flush();
    }

    public function logSecurityEvent(
        string $action,
        ?User $user = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $metadata = null
    ): void {
        $this->log($action, $user, $ipAddress, $userAgent, $metadata, 'SECURITY');
    }

    public function logError(
        string $action,
        ?User $user = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $metadata = null
    ): void {
        $this->log($action, $user, $ipAddress, $userAgent, $metadata, 'ERROR');
    }

    public function logUserAction(
        string $action,
        User $user,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $metadata = null
    ): void {
        $this->log($action, $user, $ipAddress, $userAgent, $metadata, 'INFO', 'USER');
    }

    public function logSystemEvent(
        string $action,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $metadata = null
    ): void {
        $this->log($action, null, $ipAddress, $userAgent, $metadata, 'INFO', 'SYSTEM');
    }
}
