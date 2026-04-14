<?php

namespace App\Controller;

use App\Entity\UserProfile;
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
    public function index(): Response
    {
        $user = $this->getUser();
        $profile = $user->getProfile();

        return $this->render('smart_style/index.html.twig', [
            'profile' => $profile,
        ]);
    }

    #[Route('/scan', name: 'scan')]
    public function scan(): Response
    {
        return $this->render('smart_style/scan.html.twig');
    }

    #[Route('/analyze', name: 'analyze', methods: ['POST'])]
    public function analyze(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

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

        // Validatsiya
        if ($gender !== null && $gender !== '' && !in_array($gender, UserProfile::GENDERS, true)) {
            return $this->json(['error' => 'Noto\'g\'ri jins.'], 400);
        }

        if (!$skinTone || !in_array($skinTone, UserProfile::SKIN_TONES, true)) {
            return $this->json(['error' => 'Noto\'g\'ri teri rangi.'], 400);
        }

        if (!$faceShape || !in_array($faceShape, UserProfile::FACE_SHAPES, true)) {
            return $this->json(['error' => 'Noto\'g\'ri yuz shakli.'], 400);
        }

        if ($occasion !== null && $occasion !== '' && !in_array($occasion, UserProfile::OCCASIONS, true)) {
            return $this->json(['error' => 'Noto\'g\'ri occasion.'], 400);
        }

        if ($styleIntent !== null && $styleIntent !== '' && !in_array($styleIntent, UserProfile::STYLE_INTENTS, true)) {
            return $this->json(['error' => 'Noto\'g\'ri style intent.'], 400);
        }

        if ($season !== null && $season !== '' && !in_array($season, UserProfile::SEASONS, true)) {
            return $this->json(['error' => 'Noto\'g\'ri fasl.'], 400);
        }

        $user = $this->getUser();
        $profile = $user->getProfile();

        if (!$profile) {
            $profile = new UserProfile();
            $profile->setUser($user);
            $user->setProfile($profile);
        }

        $profile->setSkinTone($skinTone);
        $profile->setFaceShape($faceShape);
        $profile->setGender($gender !== '' ? $gender : null);
        $profile->setOccasion($occasion !== '' ? $occasion : null);
        $profile->setStyleIntent($styleIntent !== '' ? $styleIntent : null);
        $profile->setSeason($season !== '' ? $season : null);
        $profile->setHeightCm($heightCm);
        $profile->setShoulderCm($shoulderCm);
        $profile->setChestCm($chestCm);
        $profile->setWaistCm($waistCm);
        $profile->setHipCm($hipCm);

        $bodyType = SmartStyleService::detectBodyType($shoulderCm, $chestCm, $waistCm, $hipCm);
        $profile->setBodyType($bodyType);

        $profile->setAnalyzedAt(new \DateTimeImmutable());

        $em->persist($profile);
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
