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

        // Validatsiya
        if (!$skinTone || !in_array($skinTone, UserProfile::SKIN_TONES)) {
            return $this->json(['error' => 'Noto\'g\'ri teri rangi.'], 400);
        }

        if (!$faceShape || !in_array($faceShape, UserProfile::FACE_SHAPES)) {
            return $this->json(['error' => 'Noto\'g\'ri yuz shakli.'], 400);
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
        $profile->setAnalyzedAt(new \DateTimeImmutable());

        $em->persist($profile);
        $em->flush();

        return $this->json([
            'success' => true,
            'skinTone' => $skinTone,
            'faceShape' => $faceShape,
            'skinToneLabel' => $profile->getSkinToneLabel(),
            'faceShapeLabel' => $profile->getFaceShapeLabel(),
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
}
