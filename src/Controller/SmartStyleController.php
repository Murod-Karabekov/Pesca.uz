<?php

namespace App\Controller;

use App\Entity\SmartStyleScanHistory;
use App\Entity\User;
use App\Entity\UserProfile;
use App\Service\SmartStyleMonthlyQuotaService;
use App\Service\SmartStyleProfileApplier;
use App\Service\SmartStyleService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/smart-style', name: 'app_smart_style_')]
#[IsGranted('ROLE_USER')]
class SmartStyleController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(SmartStyleMonthlyQuotaService $quotaService): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $profile = $user->getProfile();

        return $this->render('smart_style/index.html.twig', [
            'profile' => $profile,
            'smartStyleQuota' => $quotaService->getStateForUser($user),
        ]);
    }

    #[Route('/scan', name: 'scan')]
    public function scan(SmartStyleMonthlyQuotaService $quotaService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('smart_style/scan.html.twig', [
            'smartStyleQuota' => $quotaService->getStateForUser($user),
        ]);
    }

    #[Route('/analyze', name: 'analyze', methods: ['POST'])]
    public function analyze(
        Request $request,
        EntityManagerInterface $em,
        SmartStyleProfileApplier $profileApplier,
        SmartStyleMonthlyQuotaService $quotaService,
        SmartStyleService $smartStyleService,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?? [];

        $skinTone = $data['skinTone'] ?? null;
        $faceShape = $data['faceShape'] ?? null;
        $gender = $data['gender'] ?? null;

        $occasion = $data['occasion'] ?? null;
        $styleIntent = $data['styleIntent'] ?? null;
        $season = $data['season'] ?? null;

        $heightCm = $this->sanitizeMeasurement($data['heightCm'] ?? null);
        $shoulderCm = $this->sanitizeMeasurement($data['shoulderCm'] ?? null);
        $chestCm = $this->sanitizeMeasurement($data['chestCm'] ?? null);
        $waistCm = $this->sanitizeMeasurement($data['waistCm'] ?? null);
        $hipCm = $this->sanitizeMeasurement($data['hipCm'] ?? null);

        if (!$gender || !in_array($gender, UserProfile::GENDERS, true)) {
            return $this->json(['success' => false, 'error' => 'Jins majburiy (male yoki female).'], 400);
        }

        if (!$skinTone || !in_array($skinTone, UserProfile::SKIN_TONES, true)) {
            return $this->json(['success' => false, 'error' => 'Noto\'g\'ri teri rangi.'], 400);
        }

        if (!$faceShape || !in_array($faceShape, UserProfile::FACE_SHAPES, true)) {
            return $this->json(['success' => false, 'error' => 'Noto\'g\'ri yuz shakli.'], 400);
        }

        if ($occasion !== null && $occasion !== '' && !in_array($occasion, UserProfile::OCCASIONS, true)) {
            return $this->json(['success' => false, 'error' => 'Noto\'g\'ri occasion.'], 400);
        }

        if ($styleIntent !== null && $styleIntent !== '' && !in_array($styleIntent, UserProfile::STYLE_INTENTS, true)) {
            return $this->json(['success' => false, 'error' => 'Noto\'g\'ri style intent.'], 400);
        }

        if ($season !== null && $season !== '' && !in_array($season, UserProfile::SEASONS, true)) {
            return $this->json(['success' => false, 'error' => 'Noto\'g\'ri fasl.'], 400);
        }

        /** @var User $user */
        $user = $this->getUser();

        if ($quotaService->isBlocked($user)) {
            $state = $quotaService->getStateForUser($user);

            return $this->json([
                'success' => false,
                'code' => 'smart_style_monthly_limit',
                'error' => sprintf(
                    'Bu oy uchun SmartStyle limiti tugadi (%d/%d). Yangilanish: %s. Davom etish uchun yuqori tarifni tanlang.',
                    (int) $state['used'],
                    (int) $state['limit'],
                    (string) ($state['nextResetLabel'] ?? ''),
                ),
                'upgradeUrl' => $this->generateUrl('app_hamkorlik_index'),
                'used' => $state['used'],
                'limit' => $state['limit'],
            ], 403);
        }

        $profile = $profileApplier->applyToUser(
            $user,
            $em,
            $gender,
            $skinTone,
            $faceShape,
            $occasion !== '' ? $occasion : null,
            $styleIntent !== '' ? $styleIntent : null,
            $season !== '' ? $season : null,
            $heightCm,
            $shoulderCm,
            $chestCm,
            $waistCm,
            $hipCm,
        );

        $this->persistSmartStyleScanHistory($user, $profile, $smartStyleService, $em);
        $em->flush();

        return $this->json([
            'success' => true,
            'skinTone' => $skinTone,
            'faceShape' => $faceShape,
            'skinToneLabel' => $profile->getSkinToneLabel(),
            'faceShapeLabel' => $profile->getFaceShapeLabel(),
            'occasion' => $profile->getOccasion(),
            'styleIntent' => $profile->getStyleIntent(),
            'season' => $profile->getSeason(),
            'bodyType' => $profile->getBodyType(),
            'bodyTypeLabel' => $profile->getBodyTypeLabel(),
        ]);
    }

    #[Route('/results', name: 'results')]
    public function results(SmartStyleService $smartStyleService): Response
    {
        $user = $this->getUser();
        $profile = $user->getProfile();

        if (!$profile || !$profile->isAnalyzed()) {
            return $this->redirectToRoute('app_smart_style_scan');
        }

        $recommendations = $smartStyleService->getRecommendations($profile);
        $tips = SmartStyleService::getFaceShapeTips($profile->getFaceShape());

        return $this->render('smart_style/results.html.twig', [
            'profile' => $profile,
            'recommendations' => $recommendations,
            'tips' => $tips,
        ]);
    }

    #[Route('/clear', name: 'clear', methods: ['POST'])]
    public function clear(
        Request $request,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isCsrfTokenValid('smart_style_clear', $request->request->get('_token'))) {
            $this->addFlash('error', 'Noto\'g\'ri CSRF token.');
            return $this->redirectToRoute('app_smart_style_index');
        }

        $user = $this->getUser();
        $profile = $user->getProfile();

        if ($profile) {
            $profile->clear();
            $em->flush();
        }

        $this->addFlash('success', 'Profilingiz tozalandi.');
        return $this->redirectToRoute('app_smart_style_index');
    }

    private function persistSmartStyleScanHistory(
        User $user,
        UserProfile $profile,
        SmartStyleService $smartStyleService,
        EntityManagerInterface $em,
    ): void {
        $recommendations = $smartStyleService->getRecommendations($profile);
        $recSnapshot = array_map(static function (array $row): array {
            return [
                'id' => $row['product']->getId(),
                'name' => $row['product']->getName(),
                'score' => $row['score'],
            ];
        }, $recommendations);

        $profileSnapshot = [
            'gender' => $profile->getGender(),
            'skinTone' => $profile->getSkinTone(),
            'faceShape' => $profile->getFaceShape(),
            'occasion' => $profile->getOccasion(),
            'styleIntent' => $profile->getStyleIntent(),
            'season' => $profile->getSeason(),
            'bodyType' => $profile->getBodyType(),
            'measurements' => [
                'heightCm' => $profile->getHeightCm(),
                'shoulderCm' => $profile->getShoulderCm(),
                'chestCm' => $profile->getChestCm(),
                'waistCm' => $profile->getWaistCm(),
                'hipCm' => $profile->getHipCm(),
            ],
            'source' => 'web',
        ];

        $history = new SmartStyleScanHistory();
        $history->setUser($user);
        $history->setProfileSnapshot($profileSnapshot);
        $history->setRecommendationsSnapshot($recSnapshot);
        $em->persist($history);
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
