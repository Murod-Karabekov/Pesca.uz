<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class LoginSuccessSubscriber implements EventSubscriberInterface
{
    public function __construct(private RouterInterface $router)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getAuthenticatedToken()->getUser();
        $roles = method_exists($user, 'getRoles') ? $user->getRoles() : [];

        if (in_array('ROLE_ADMIN', $roles, true)) {
            $event->setResponse(new RedirectResponse($this->router->generate('admin_dashboard')));
        } elseif (in_array('ROLE_VENDOR', $roles, true)) {
            $event->setResponse(new RedirectResponse($this->router->generate('vendor_dashboard')));
        }
        // ROLE_USER → default_target_path (app_home) ishlaydi
    }
}
