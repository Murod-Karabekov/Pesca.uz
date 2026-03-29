<?php

namespace App\Controller\Admin;

use App\Entity\BonusTransaction;
use App\Entity\Withdrawal;
use App\Repository\WithdrawalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/withdrawals', name: 'admin_withdrawal_')]
#[IsGranted('ROLE_ADMIN')]
class WithdrawalController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(WithdrawalRepository $repo): Response
    {
        return $this->render('admin/withdrawal/index.html.twig', [
            'withdrawals' => $repo->findAll(),
            'pendingCount' => count($repo->findPending()),
        ]);
    }

    #[Route('/{id}/approve', name: 'approve', methods: ['POST'])]
    public function approve(
        Withdrawal $withdrawal,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('approve_' . $withdrawal->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Xavfsizlik xatosi.');
            return $this->redirectToRoute('admin_withdrawal_index');
        }

        $withdrawal->setStatus(Withdrawal::STATUS_COMPLETED);
        $withdrawal->setProcessedAt(new \DateTimeImmutable());
        $withdrawal->setAdminNote($request->request->get('note', 'Tasdiqlandi'));

        // "Yechildi" tranzaksiyasini yozish
        $tx = new BonusTransaction();
        $tx->setUser($withdrawal->getUser());
        $tx->setType(BonusTransaction::TYPE_WITHDRAWN);
        $tx->setAmount($withdrawal->getAmount());
        $tx->setDescription(sprintf('Naqd yechildi — %s ga o\'tkazildi', $withdrawal->getCardNumber()));
        $em->persist($tx);

        $em->flush();

        $this->addFlash('success', sprintf(
            '%s so\'m — %s ga o\'tkazildi.',
            number_format((float) $withdrawal->getAmount(), 0, '.', ' '),
            $withdrawal->getUser()->getFullName()
        ));

        return $this->redirectToRoute('admin_withdrawal_index');
    }

    #[Route('/{id}/reject', name: 'reject', methods: ['POST'])]
    public function reject(
        Withdrawal $withdrawal,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('reject_' . $withdrawal->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Xavfsizlik xatosi.');
            return $this->redirectToRoute('admin_withdrawal_index');
        }

        $reason = $request->request->get('note', 'Rad etildi');

        $withdrawal->setStatus(Withdrawal::STATUS_REJECTED);
        $withdrawal->setProcessedAt(new \DateTimeImmutable());
        $withdrawal->setAdminNote($reason);

        // Mablag'ni qaytarish
        $wallet = $withdrawal->getUser()->getBonusWallet();
        if ($wallet) {
            $wallet->credit($withdrawal->getAmount());
        }

        // "Qaytarildi" tranzaksiyasini yozish
        $tx = new BonusTransaction();
        $tx->setUser($withdrawal->getUser());
        $tx->setType(BonusTransaction::TYPE_WITHDRAWAL_REFUNDED);
        $tx->setAmount($withdrawal->getAmount());
        $tx->setDescription(sprintf('Yechish rad etildi: %s', $reason));
        $em->persist($tx);

        $em->flush();

        $this->addFlash('warning', sprintf(
            '%s ning yechish so\'rovi rad etildi. Mablag\' qaytarildi.',
            $withdrawal->getUser()->getFullName()
        ));

        return $this->redirectToRoute('admin_withdrawal_index');
    }
}
