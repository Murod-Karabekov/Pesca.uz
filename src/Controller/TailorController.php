<?php

namespace App\Controller;

use App\Entity\Tailor;
use App\Repository\TailorRepository;
use App\Service\GoogleFormService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/premium-service', name: 'app_tailor_')]
class TailorController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(TailorRepository $tailorRepository): Response
    {
        return $this->render('tailor/index.html.twig', [
            'tailors' => $tailorRepository->findAllOrdered(),
        ]);
    }

    #[Route('/{id}/book', name: 'book', methods: ['POST'])]
    public function book(
        Tailor $tailor,
        Request $request,
        GoogleFormService $googleFormService
    ): Response {
        if (!$this->getUser()) {
            $this->addFlash('error', 'Tikuvchiga buyurtma berish uchun tizimga kiring.');
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isCsrfTokenValid('tailor_book_' . $tailor->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Noto\'g\'ri CSRF token.');
            return $this->redirectToRoute('app_tailor_index');
        }

        $user = $this->getUser();

        $googleFormService->submitTailorBooking(
            $user->getFullName(),
            $user->getPhone(),
            $tailor->getName()
        );

        $this->addFlash('success', $tailor->getName() . ' bilan buyurtmangiz yuborildi!');
        return $this->redirectToRoute('app_tailor_index');
    }
}
