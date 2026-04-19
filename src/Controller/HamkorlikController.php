<?php

namespace App\Controller;

use App\Entity\CorporatePartnershipRequest;
use App\Entity\MembershipPlan;
use App\Entity\User;
use App\Form\CorporatePartnershipRequestType;
use App\Repository\MembershipPlanRepository;
use App\Service\MembershipService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/hamkorlik')]
class HamkorlikController extends AbstractController
{
    /**
     * Tariflar ro'yxati
     */
    #[Route('', name: 'app_hamkorlik_index')]
    public function index(MembershipPlanRepository $planRepo): Response
    {
        $plans = $planRepo->findPaidPlans();

        return $this->render('hamkorlik/index.html.twig', [
            'plans' => $plans,
            'currentPlan' => $this->getUser()?->getCurrentPlan() ?? 'free',
        ]);
    }

    /**
     * Korporativ hamkorlik — so'rov formasi (B2B)
     */
    #[Route('/korporativ', name: 'app_hamkorlik_corporate', methods: ['GET', 'POST'], priority: 10)]
    public function corporate(Request $request, EntityManagerInterface $em): Response
    {
        $entity = new CorporatePartnershipRequest();
        $user = $this->getUser();
        if ($user instanceof User) {
            $entity->setSubmittedByUser($user);
            $entity->setContactFullName((string) $user->getFullName());
            $entity->setPhone((string) $user->getPhone());
        }

        $form = $this->createForm(CorporatePartnershipRequestType::class, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($entity);
            $em->flush();
            $this->addFlash('success', 'So‘rovingiz qabul qilindi. Tez orada siz bilan bog‘lanamiz.');

            return $this->redirectToRoute('app_hamkorlik_corporate');
        }

        return $this->render('hamkorlik/corporate.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Tarif tafsilotlari
     */
    #[Route('/{slug}', name: 'app_hamkorlik_show')]
    public function show(string $slug, MembershipPlanRepository $planRepo, MembershipService $membershipService): Response
    {
        $plan = $planRepo->findBySlug($slug);

        if (!$plan || $plan->isFree()) {
            throw $this->createNotFoundException('Tarif topilmadi');
        }

        $user = $this->getUser();
        $canUpgrade = $user ? $membershipService->canUpgradeTo($user, $plan) : true;

        return $this->render('hamkorlik/show.html.twig', [
            'plan' => $plan,
            'canUpgrade' => $canUpgrade,
        ]);
    }

    /**
     * Tarifga a'zo bo'lish
     */
    #[Route('/{slug}/subscribe', name: 'app_hamkorlik_subscribe', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function subscribe(
        string $slug,
        Request $request,
        MembershipPlanRepository $planRepo,
        MembershipService $membershipService,
    ): Response {
        $plan = $planRepo->findBySlug($slug);

        if (!$plan || $plan->isFree()) {
            throw $this->createNotFoundException('Tarif topilmadi');
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Upgrade mumkinmi tekshirish
        if (!$membershipService->canUpgradeTo($user, $plan)) {
            $this->addFlash('error', 'Siz allaqachon bu tarif yoki undan yuqori tarifga a\'zosiz.');
            return $this->redirectToRoute('app_hamkorlik_index');
        }

        // CSRF tekshirish
        if (!$this->isCsrfTokenValid('subscribe_' . $slug, $request->request->get('_token'))) {
            $this->addFlash('error', 'Xavfsizlik xatosi. Qaytadan urinib ko\'ring.');
            return $this->redirectToRoute('app_hamkorlik_show', ['slug' => $slug]);
        }

        $paymentMethod = $request->request->get('payment_method', 'manual');

        // A'zo qilish
        try {
            $membershipService->subscribeToPlan($user, $plan, $paymentMethod);
        } catch (\LogicException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('app_hamkorlik_show', ['slug' => $slug]);
        }

        $this->addFlash('success', sprintf(
            '🎉 Tabriklaymiz! Siz "%s" tarifga muvaffaqiyatli a\'zo bo\'ldingiz!',
            $plan->getName()
        ));

        return $this->redirectToRoute('app_hamkorlik_success');
    }

    /**
     * Muvaffaqiyatli a'zo bo'lgandan keyin
     */
    #[Route('/success', name: 'app_hamkorlik_success', priority: 10)]
    #[IsGranted('ROLE_USER')]
    public function success(MembershipService $membershipService): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $plan = $membershipService->getUserPlan($user);

        return $this->render('hamkorlik/success.html.twig', [
            'plan' => $plan,
        ]);
    }
}
