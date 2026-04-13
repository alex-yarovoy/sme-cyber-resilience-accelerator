<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RequestIdSubscriber implements EventSubscriberInterface
{
    private const HEADER = 'X-Request-ID';
    private ?string $id = null;

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 10000],
            KernelEvents::RESPONSE => ['onResponse', -10000],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $this->id = $request->headers->get(self::HEADER) ?: bin2hex(random_bytes(8));
        $request->headers->set(self::HEADER, $this->id);
        $request->attributes->set('request_id', $this->id);
    }

    public function onResponse(ResponseEvent $event): void
    {
        if ($this->id) {
            $event->getResponse()->headers->set(self::HEADER, $this->id);
        }
    }
}



