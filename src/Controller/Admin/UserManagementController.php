<?php

namespace App\Controller\Admin;

use App\Entity\BonusTransaction;
use App\Entity\User;
use App\Repository\BonusTransactionRepository;
use App\Repository\BonusWalletRepository;
use App\Repository\MembershipPlanRepository;
use App\Repository\UserRepository;
use App\Service\BonusService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users', name: 'admin_user_')]
#[IsGranted('ROLE_ADMIN')]
class UserManagementController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(UserRepository $userRepository, Request $request): Response
    {
        $search = trim($request->query->get('q', ''));

        if ($search !== '') {
            $users = $userRepository->searchByPhone($search);
        } else {
            $users = $userRepository->findBy([], ['createdAt' => 'DESC']);
        }

        return $this->render('admin/user/index.html.twig', [
            'users' => $users,
            'search' => $search,
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'])]
    public function show(
        User $user,
        BonusService $bonusService,
        BonusTransactionRepository $txRepo,
        MembershipPlanRepository $planRepo,
    ): Response {
        $bonusBalance = $bonusService->getBalance($user);
        $transactions = $txRepo->findBy(['user' => $user], ['createdAt' => 'DESC'], 20);
        $plans = $planRepo->findBy(['isActive' => true], ['sortOrder' => 'ASC']);

        return $this->render('admin/user/show.html.twig', [
            'targetUser' => $user,
            'bonusBalance' => $bonusBalance,
            'transactions' => $transactions,
            'plans' => $plans,
        ]);
    }

    #[Route('/{id}/plan', name: 'update_plan', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function updatePlan(
        User $user,
        Request $request,
        EntityManagerInterface $em,
        MembershipPlanRepository $planRepo,
    ): Response {
        if (!$this->isCsrfTokenValid('update_plan_' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Noto\'g\'ri CSRF token.');
            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }

        $slug = $request->request->get('plan');
        $plan = $planRepo->findBySlug($slug);

        if (!$plan) {
            $this->addFlash('error', 'Tarif topilmadi.');
            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }

        $user->setCurrentPlan($plan->getSlug());
        $em->flush();

        $this->addFlash('success', $user->getFullName() . ' tarifi "' . $plan->getName() . '" ga o\'zgartirildi.');
        return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
    }

    #[Route('/{id}/balance', name: 'update_balance', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function updateBalance(
        User $user,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('update_balance_' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Noto\'g\'ri CSRF token.');
            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }

        $action = $request->request->get('action'); // 'add' or 'deduct'
        $amount = trim($request->request->get('amount', '0'));
        $note = trim($request->request->get('note', ''));

        // Summa tekshirish
        if (!is_numeric($amount) || bccomp($amount, '0', 2) <= 0) {
            $this->addFlash('error', 'Noto\'g\'ri summa kiritildi.');
            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }

        $amount = number_format((float) $amount, 2, '.', '');

        if ($action === 'add') {
            $user->addGeneralBalance($amount);

            $tx = new BonusTransaction();
            $tx->setUser($user);
            $tx->setType(BonusTransaction::TYPE_ADMIN_TOPUP);
            $tx->setAmount($amount);
            $tx->setDescription($note ?: 'Admin tomonidan umumiy balans to\'ldirildi');
            $em->persist($tx);

            $this->addFlash('success', number_format((float) $amount, 0, '.', ' ') . ' so\'m umumiy balansga qo\'shildi.');
        } elseif ($action === 'deduct') {
            if (bccomp($user->getGeneralBalance(), $amount, 2) < 0) {
                $this->addFlash('error', 'Umumiy balansda yetarli mablag\' yo\'q.');
                return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
            }
            $user->deductGeneralBalance($amount);

            $tx = new BonusTransaction();
            $tx->setUser($user);
            $tx->setType(BonusTransaction::TYPE_ADMIN_DEDUCT);
            $tx->setAmount($amount);
            $tx->setDescription($note ?: 'Admin tomonidan umumiy balansdan yechildi');
            $em->persist($tx);

            $this->addFlash('success', number_format((float) $amount, 0, '.', ' ') . ' so\'m umumiy balansdan yechildi.');
        } else {
            $this->addFlash('error', 'Noto\'g\'ri amal.');
            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }

        $em->flush();
        return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
    }

    #[Route('/{id}/bonus', name: 'update_bonus', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function updateBonus(
        User $user,
        Request $request,
        EntityManagerInterface $em,
        BonusWalletRepository $walletRepo,
    ): Response {
        if (!$this->isCsrfTokenValid('update_bonus_' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Noto\'g\'ri CSRF token.');
            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }

        $action = $request->request->get('action');
        $amount = trim($request->request->get('amount', '0'));
        $note = trim($request->request->get('note', ''));

        if (!is_numeric($amount) || bccomp($amount, '0', 2) <= 0) {
            $this->addFlash('error', 'Noto\'g\'ri summa kiritildi.');
            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }

        $amount = number_format((float) $amount, 2, '.', '');
        $wallet = $walletRepo->findOrCreateByUser($user);

        if ($action === 'add') {
            $wallet->credit($amount);

            $tx = new BonusTransaction();
            $tx->setUser($user);
            $tx->setType(BonusTransaction::TYPE_ADMIN_TOPUP);
            $tx->setAmount($amount);
            $tx->setDescription($note ?: 'Admin tomonidan bonus balans to\'ldirildi');
            $em->persist($tx);

            $this->addFlash('success', number_format((float) $amount, 0, '.', ' ') . ' so\'m bonus balansga qo\'shildi.');
        } elseif ($action === 'deduct') {
            if (bccomp($wallet->getBalance(), $amount, 2) < 0) {
                $this->addFlash('error', 'Bonus balansda yetarli mablag\' yo\'q.');
                return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
            }
            $wallet->debit($amount);

            $tx = new BonusTransaction();
            $tx->setUser($user);
            $tx->setType(BonusTransaction::TYPE_ADMIN_DEDUCT);
            $tx->setAmount($amount);
            $tx->setDescription($note ?: 'Admin tomonidan bonus balansdan yechildi');
            $em->persist($tx);

            $this->addFlash('success', number_format((float) $amount, 0, '.', ' ') . ' so\'m bonus balansdan yechildi.');
        } else {
            $this->addFlash('error', 'Noto\'g\'ri amal.');
            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }

        $em->flush();
        return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
    }

    #[Route('/assign-role', name: 'assign_role', methods: ['POST'])]
    public function assignRole(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isCsrfTokenValid('assign_role', $request->request->get('_token'))) {
            $this->addFlash('error', 'Noto\'g\'ri CSRF token.');
            return $this->redirectToRoute('admin_user_index');
        }

        $phone = $request->request->get('phone');
        $role = $request->request->get('role');

        if (!in_array($role, ['ROLE_USER', 'ROLE_ADMIN'])) {
            $this->addFlash('error', 'Noto\'g\'ri rol.');
            return $this->redirectToRoute('admin_user_index');
        }

        $user = $userRepository->findOneByPhone($phone);
        if (!$user) {
            $this->addFlash('error', '"' . $phone . '" telefonli foydalanuvchi topilmadi.');
            return $this->redirectToRoute('admin_user_index');
        }

        if ($role === 'ROLE_ADMIN') {
            $user->setRoles(['ROLE_ADMIN']);
        } else {
            $user->setRoles(['ROLE_USER']);
        }

        $em->flush();

        $this->addFlash('success', $user->getFullName() . ' uchun rol yangilandi.');
        return $this->redirectToRoute('admin_user_index');
    }
}
