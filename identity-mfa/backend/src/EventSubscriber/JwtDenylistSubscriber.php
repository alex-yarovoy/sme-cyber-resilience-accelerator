<?php

namespace App\EventSubscriber;

use App\Service\TokenDenylistService;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JwtDenylistSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly TokenDenylistService $tokenDenylist)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::JWT_CREATED => 'onJwtCreated',
            Events::JWT_DECODED => 'onJwtDecoded',
        ];
    }

    public function onJwtCreated(JWTCreatedEvent $event): void
    {
        $data = $event->getData();
        if (!isset($data['jti']) || !is_string($data['jti']) || $data['jti'] === '') {
            $data['jti'] = bin2hex(random_bytes(16));
            $event->setData($data);
        }
    }

    public function onJwtDecoded(JWTDecodedEvent $event): void
    {
        $payload = $event->getPayload();
        $tokenId = $payload['jti'] ?? null;
        if (!is_string($tokenId) || $tokenId === '') {
            return;
        }

        if ($this->tokenDenylist->isDenied($tokenId)) {
            $event->markAsInvalid();
        }
    }
}
