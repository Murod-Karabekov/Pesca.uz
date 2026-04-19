<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\MobileAuthTokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Mobil ilovadan WebView sessiyasini sinxronlash: Bearer token → Symfony session.
 */
class MobileWebBridgeController extends AbstractController
{
    #[Route('/mobile/web-login', name: 'app_mobile_web_login', methods: ['GET'])]
    public function webLogin(
        Request $request,
        MobileAuthTokenService $tokens,
        UserRepository $users,
        Security $security,
    ): Response {
        $token = $request->query->get('token');
        if (!\is_string($token) || $token === '') {
            return $this->redirectToRoute('app_login');
        }

        $user = $tokens->verifyAndLoadUser($token, $users);
        if ($user === null) {
            return $this->redirectToRoute('app_login');
        }

        $target = $request->query->get('target', '/profil');
        $target = $this->sanitizeTargetPath(\is_string($target) ? $target : '/profil');

        // Sessiya token storage’da yoziladi; form_login success redirect o‘rniga kerakli sahifaga yo‘naltiramiz.
        $security->login($user, 'form_login', 'main');

        return new RedirectResponse($target);
    }

    private function sanitizeTargetPath(string $target): string
    {
        $target = trim($target);
        if ($target === '' || !str_starts_with($target, '/')) {
            return '/profil';
        }
        if (str_contains($target, '//') || str_contains($target, "\0")) {
            return '/profil';
        }
        if (!preg_match('#^/[a-zA-Z0-9/_\-?=&.%]*$#', $target)) {
            return '/profil';
        }

        return $target;
    }
}
