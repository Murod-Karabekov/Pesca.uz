<?php

namespace App\Controller\Api;

use App\Entity\Product;
use App\Entity\SmartStyleScanHistory;
use App\Entity\UserProfile;
use App\Repository\SmartStyleScanHistoryRepository;
use App\Repository\UserRepository;
use App\Service\MobileAuthTokenService;
use App\Service\SmartStyleMonthlyQuotaService;
use App\Service\SmartStyleProfileApplier;
use App\Service\SmartStyleService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/smart-style')]
class SmartStyleApiController extends AbstractController
{
    public function __construct(
        #[Autowire('%env(MOBILE_SMARTSTYLE_KEY)%')]
        private readonly string $mobileSmartStyleKey,
    ) {
    }

    #[Route('/analyze', name: 'api_smart_style_analyze', methods: ['POST'])]
    public function analyze(
        Request $request,
        SmartStyleService $smartStyleService,
        MobileAuthTokenService $mobileAuthTokenService,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        SmartStyleProfileApplier $profileApplier,
        SmartStyleMonthlyQuotaService $quotaService,
    ): JsonResponse {
        $sentKey = (string) $request->headers->get('X-Pesca-Key', '');
        if ($sentKey === '' || !hash_equals($this->mobileSmartStyleKey, $sentKey)) {
            return $this->json(
                ['error' => 'Noto\'g\'ri yoki yo\'q API kalit. Sarlavha: X-Pesca-Key.'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        $usedAssumedProfile = false;

        $gender = $this->resolveEnumField(
            $request->request->get('gender'),
            UserProfile::GENDERS,
            'female',
            'Jins noto\'g\'ri (male yoki female).',
            $usedAssumedProfile,
        );
        if ($gender instanceof JsonResponse) {
            return $gender;
        }

        $skinTone = $this->resolveEnumField(
            $request->request->get('skinTone'),
            UserProfile::SKIN_TONES,
            'warm_medium',
            'Teri rangi noto\'g\'ri.',
            $usedAssumedProfile,
        );
        if ($skinTone instanceof JsonResponse) {
            return $skinTone;
        }

        $faceShape = $this->resolveEnumField(
            $request->request->get('faceShape'),
            UserProfile::FACE_SHAPES,
            'oval',
            'Yuz shakli noto\'g\'ri.',
            $usedAssumedProfile,
        );
        if ($faceShape instanceof JsonResponse) {
            return $faceShape;
        }

        $occasion = $request->request->get('occasion');
        $styleIntent = $request->request->get('styleIntent');
        $season = $request->request->get('season');

        if ($occasion !== null && $occasion !== '' && !in_array($occasion, UserProfile::OCCASIONS, true)) {
            return $this->json(['error' => 'Noto\'g\'ri occasion.'], Response::HTTP_BAD_REQUEST);
        }

        if ($styleIntent !== null && $styleIntent !== '' && !in_array($styleIntent, UserProfile::STYLE_INTENTS, true)) {
            return $this->json(['error' => 'Noto\'g\'ri style intent.'], Response::HTTP_BAD_REQUEST);
        }

        if ($season !== null && $season !== '' && !in_array($season, UserProfile::SEASONS, true)) {
            return $this->json(['error' => 'Noto\'g\'ri fasl.'], Response::HTTP_BAD_REQUEST);
        }

        $heightCm = $this->sanitizeMeasurement($request->request->get('heightCm'));
        $shoulderCm = $this->sanitizeMeasurement($request->request->get('shoulderCm'));
        $chestCm = $this->sanitizeMeasurement($request->request->get('chestCm'));
        $waistCm = $this->sanitizeMeasurement($request->request->get('waistCm'));
        $hipCm = $this->sanitizeMeasurement($request->request->get('hipCm'));

        $bodyType = SmartStyleService::detectBodyType($shoulderCm, $chestCm, $waistCm, $hipCm, $gender);

        $recommendations = $smartStyleService->recommendForAttributes(
            $skinTone,
            $faceShape,
            $gender,
            $occasion !== null && $occasion !== '' ? $occasion : null,
            $bodyType,
            $season !== null && $season !== '' ? $season : null,
            50,
        );

        $photoMeta = null;
        $photoFilename = null;
        $photo = $request->files->get('photo');
        if ($photo instanceof UploadedFile && $photo->isValid()) {
            $uploadDir = $this->getParameter('kernel.project_dir') . '/var/smartstyle_mobile_uploads';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }
            $ext = $photo->guessExtension() ?: 'jpg';
            $safe = bin2hex(random_bytes(8)) . '.' . $ext;
            $photo->move($uploadDir, $safe);
            $photoMeta = ['stored' => true, 'filename' => $safe];
            $photoFilename = $safe;
        }

        $base = $request->getSchemeAndHttpHost();
        $items = [];
        foreach ($recommendations as $row) {
            /** @var Product $p */
            $p = $row['product'];
            $src = $p->getImageSrc();
            $imageUrl = $src === null ? null : ($p->isExternalImage() ? $src : $base . $src);

            $items[] = [
                'id' => $p->getId(),
                'name' => $p->getName(),
                'price' => $p->getPrice(),
                'currency' => 'UZS',
                'imageUrl' => $imageUrl,
                'score' => $row['score'],
                'scoreLabel' => SmartStyleService::getScoreLabel($row['score']),
            ];
        }

        $profilePayload = [
            'gender' => $gender,
            'skinTone' => $skinTone,
            'faceShape' => $faceShape,
            'occasion' => $occasion !== null && $occasion !== '' ? $occasion : null,
            'styleIntent' => $styleIntent !== null && $styleIntent !== '' ? $styleIntent : null,
            'season' => $season !== null && $season !== '' ? $season : null,
            'bodyType' => $bodyType,
            'measurements' => [
                'heightCm' => $heightCm,
                'shoulderCm' => $shoulderCm,
                'chestCm' => $chestCm,
                'waistCm' => $waistCm,
                'hipCm' => $hipCm,
            ],
        ];

        $apiUser = $mobileAuthTokenService->getUserFromRequest($request, $userRepository);
        if ($apiUser !== null) {
            if ($quotaService->isBlocked($apiUser)) {
                $state = $quotaService->getStateForUser($apiUser);

                return $this->json([
                    'success' => false,
                    'code' => 'smart_style_monthly_limit',
                    'error' => sprintf(
                        'Bu oy uchun SmartStyle limiti tugadi (%d/%d). Yangilanish: %s.',
                        (int) $state['used'],
                        (int) $state['limit'],
                        (string) ($state['nextResetLabel'] ?? ''),
                    ),
                    'used' => $state['used'],
                    'limit' => $state['limit'],
                ], Response::HTTP_FORBIDDEN);
            }

            $profileApplier->applyToUser(
                $apiUser,
                $em,
                $gender,
                $skinTone,
                $faceShape,
                $occasion !== null && $occasion !== '' ? $occasion : null,
                $styleIntent !== null && $styleIntent !== '' ? $styleIntent : null,
                $season !== null && $season !== '' ? $season : null,
                $heightCm,
                $shoulderCm,
                $chestCm,
                $waistCm,
                $hipCm,
            );

            $history = new SmartStyleScanHistory();
            $history->setUser($apiUser);
            $history->setProfileSnapshot($profilePayload);
            $history->setRecommendationsSnapshot(array_map(
                static fn(array $i): array => [
                    'id' => $i['id'],
                    'name' => $i['name'],
                    'score' => $i['score'],
                ],
                $items,
            ));
            $history->setPhotoFilename($photoFilename);
            $em->persist($history);
            $em->flush();
        }

        return $this->json([
            'success' => true,
            'usedAssumedProfile' => $usedAssumedProfile,
            'savedToAccount' => $apiUser !== null,
            'message' => $usedAssumedProfile
                ? 'Ba\'zi profil maydonlari standart qiymat bilan to\'ldirildi (mobil tez start). Keyinroq aniq tanlaysiz.'
                : null,
            'profile' => $profilePayload,
            'tips' => SmartStyleService::getFaceShapeTips($faceShape),
            'recommendations' => $items,
            'photo' => $photoMeta,
        ]);
    }

    #[Route('/history', name: 'api_smart_style_history', methods: ['GET'])]
    public function history(
        Request $request,
        MobileAuthTokenService $mobileAuthTokenService,
        UserRepository $userRepository,
        SmartStyleScanHistoryRepository $historyRepository,
    ): JsonResponse {
        $sentKey = (string) $request->headers->get('X-Pesca-Key', '');
        if ($sentKey === '' || !hash_equals($this->mobileSmartStyleKey, $sentKey)) {
            return $this->json(
                ['error' => 'Noto\'g\'ri yoki yo\'q API kalit.'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        $user = $mobileAuthTokenService->getUserFromRequest($request, $userRepository);
        if ($user === null) {
            return $this->json(
                ['error' => 'Tarixni ko\'rish uchun kirish kerak (Authorization: Bearer).'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        $rows = $historyRepository->findRecentByUser($user, 40);
        $out = [];
        foreach ($rows as $h) {
            $out[] = [
                'id' => $h->getId(),
                'createdAt' => $h->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'profile' => $h->getProfileSnapshot(),
                'recommendations' => $h->getRecommendationsSnapshot(),
                'photoFilename' => $h->getPhotoFilename(),
            ];
        }

        return $this->json([
            'success' => true,
            'items' => $out,
        ]);
    }

    /**
     * @param list<string> $allowed
     * @return string|JsonResponse
     */
    private function resolveEnumField(
        mixed $raw,
        array $allowed,
        string $default,
        string $invalidMessage,
        bool &$usedAssumedProfile,
    ): string|JsonResponse {
        if ($raw === null || $raw === '') {
            $usedAssumedProfile = true;

            return $default;
        }

        if (!is_string($raw) || !in_array($raw, $allowed, true)) {
            return $this->json(['error' => $invalidMessage], Response::HTTP_BAD_REQUEST);
        }

        return $raw;
    }

    private function sanitizeMeasurement(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        $intValue = (int) round((float) $value);

        if ($intValue <= 0 || $intValue > 300) {
            return null;
        }

        return $intValue;
    }
}
