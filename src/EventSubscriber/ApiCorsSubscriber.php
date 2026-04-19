<?php

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Mobil / Flutter web uchun sodda CORS (/api/*).
 */
class ApiCorsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire('%kernel.environment%')]
        private readonly string $kernelEnvironment,
        #[Autowire('%env(CORS_ALLOW_ORIGIN)%')]
        private readonly string $corsAllowOrigin,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 300],
            KernelEvents::RESPONSE => ['onKernelResponse', -10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$this->isApiPath($request)) {
            return;
        }

        if ($request->getMethod() === Request::METHOD_OPTIONS) {
            $event->setResponse(new Response('', Response::HTTP_NO_CONTENT));
        }
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$this->isApiPath($request)) {
            return;
        }

        $response = $event->getResponse();
        $allowOrigin = $this->corsAllowOrigin;
        if ($allowOrigin === '' && $this->kernelEnvironment === 'dev') {
            $allowOrigin = '*';
        }
        if ($allowOrigin === '') {
            return;
        }

        $response->headers->set('Access-Control-Allow-Origin', $allowOrigin);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, PATCH, DELETE');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, X-Pesca-Key, Authorization');
        $response->headers->set('Access-Control-Max-Age', '3600');
    }

    private function isApiPath(Request $request): bool
    {
        return str_starts_with($request->getPathInfo(), '/api/');
    }
}
