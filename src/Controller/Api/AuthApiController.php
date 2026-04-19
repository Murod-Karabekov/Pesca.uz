<?php

namespace App\Controller\Api;

use App\Repository\UserRepository;
use App\Service\MobileAuthTokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth')]
class AuthApiController extends AbstractController
{
    #[Route('/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        MobileAuthTokenService $tokenService,
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $this->json(['error' => 'JSON noto\'g\'ri.'], Response::HTTP_BAD_REQUEST);
        }

        $phone = isset($data['phone']) ? trim((string) $data['phone']) : '';
        $password = isset($data['password']) ? (string) $data['password'] : '';

        if ($phone === '' || $password === '') {
            return $this->json(['error' => 'Telefon va parol majburiy.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $userRepository->findOneByPhone($phone);
        if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
            return $this->json(['error' => 'Telefon yoki parol noto\'g\'ri.'], Response::HTTP_UNAUTHORIZED);
        }

        $token = $tokenService->createToken($user);

        return $this->json([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'fullName' => $user->getFullName(),
                'phone' => $user->getPhone(),
            ],
        ]);
    }

    #[Route('/me', name: 'api_auth_me', methods: ['GET'])]
    public function me(
        Request $request,
        UserRepository $userRepository,
        MobileAuthTokenService $tokenService,
    ): JsonResponse {
        $user = $tokenService->getUserFromRequest($request, $userRepository);
        if (!$user) {
            return $this->json(['error' => 'Kirish talab qilinadi. Authorization: Bearer ...'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'success' => true,
            'user' => [
                'id' => $user->getId(),
                'fullName' => $user->getFullName(),
                'phone' => $user->getPhone(),
            ],
        ]);
    }
}
