<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;

/**
 * Mobil ilova uchun HMAC imzoli stateless token (JWT o‘rniga sodda yechim).
 */
class MobileAuthTokenService
{
    public function __construct(
        #[Autowire('%kernel.secret%')]
        private readonly string $appSecret,
    ) {
    }

    public function createToken(User $user, int $ttlSeconds = 2592000): string
    {
        $payload = json_encode([
            'sub' => $user->getId(),
            'exp' => time() + $ttlSeconds,
        ], JSON_THROW_ON_ERROR);

        $b64 = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
        $sig = hash_hmac('sha256', $b64, $this->appSecret, true);
        $sigB64 = rtrim(strtr(base64_encode($sig), '+/', '-_'), '=');

        return $b64 . '.' . $sigB64;
    }

    public function verifyAndLoadUser(string $token, UserRepository $users): ?User
    {
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$b64, $sigB64] = $parts;
        $expected = hash_hmac('sha256', $b64, $this->appSecret, true);
        $sig = base64_decode(strtr($sigB64, '-_', '+/'), true);
        if ($sig === false || !hash_equals($expected, $sig)) {
            return null;
        }

        $json = base64_decode(strtr($b64, '-_', '+/'), true);
        if ($json === false) {
            return null;
        }

        try {
            /** @var array{sub: int, exp: int} $data */
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (!isset($data['sub'], $data['exp']) || $data['exp'] < time()) {
            return null;
        }

        return $users->find($data['sub']);
    }

    public function getUserFromRequest(Request $request, UserRepository $users): ?User
    {
        $header = (string) $request->headers->get('Authorization', '');
        if (!str_starts_with($header, 'Bearer ')) {
            return null;
        }

        $token = trim(substr($header, 7));
        if ($token === '') {
            return null;
        }

        return $this->verifyAndLoadUser($token, $users);
    }
}
