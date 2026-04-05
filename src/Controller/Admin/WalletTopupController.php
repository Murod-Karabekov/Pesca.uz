<?php

namespace App\Controller\Admin;

use App\Entity\BonusTransaction;
use App\Entity\WalletTopupRequest;
use App\Repository\WalletTopupRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/wallet-topups', name: 'admin_wallet_topup_')]
#[IsGranted('ROLE_ADMIN')]
class WalletTopupController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(WalletTopupRequestRepository $repo): Response
    {
        return $this->render('admin/wallet_topup/index.html.twig', [
            'requests' => $repo->findNewRequests(),
            'historyCount' => $repo->countHistoryRequests(),
        ]);
    }

    #[Route('/history', name: 'history')]
    public function history(Request $request, WalletTopupRequestRepository $repo): Response
    {
        $query = trim((string) $request->query->get('q', ''));

        return $this->render('admin/wallet_topup/history.html.twig', [
            'requests' => $repo->findHistoryRequests($query),
            'query' => $query,
            'newCount' => $repo->countNewRequests(),
        ]);
    }

    #[Route('/{id}/approve', name: 'approve', methods: ['POST'])]
    public function approve(
        WalletTopupRequest $topupRequest,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('approve_' . $topupRequest->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Xavfsizlik xatosi.');
            return $this->redirectToRoute('admin_wallet_topup_index');
        }

        if (!$topupRequest->isPending()) {
            $this->addFlash('warning', 'Bu so\'rov allaqachon ko\'rib chiqilgan.');
            return $this->redirectToRoute('admin_wallet_topup_index');
        }

        $topupRequest->setStatus(WalletTopupRequest::STATUS_APPROVED);
        $topupRequest->setProcessedAt(new \DateTimeImmutable());
        $topupRequest->setAdminNote(trim($request->request->get('note', 'To\'lov tasdiqlandi')));

        $user = $topupRequest->getUser();
        if (!$user) {
            $this->addFlash('error', 'Foydalanuvchi topilmadi.');
            return $this->redirectToRoute('admin_wallet_topup_index');
        }

        $user->addGeneralBalance($topupRequest->getAmount());

        $tx = new BonusTransaction();
        $tx->setUser($user);
        $tx->setType(BonusTransaction::TYPE_WALLET_TOPUP_APPROVED);
        $tx->setAmount($topupRequest->getAmount());
        $tx->setDescription(sprintf(
            'Top-up tasdiqlandi (%s). Ref: %s',
            $topupRequest->getMethodLabel(),
            $topupRequest->getPaymentReference() ?: 'ko\'rsatilmagan'
        ));
        $em->persist($tx);

        $em->flush();

        $this->addFlash('success', sprintf(
            '%s uchun %s so\'m balansga qo\'shildi.',
            $user->getFullName(),
            number_format((float) $topupRequest->getAmount(), 0, '.', ' ')
        ));

        return $this->redirectToRoute('admin_wallet_topup_index');
    }

    #[Route('/{id}/reject', name: 'reject', methods: ['POST'])]
    public function reject(
        WalletTopupRequest $topupRequest,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        if (!$this->isCsrfTokenValid('reject_' . $topupRequest->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Xavfsizlik xatosi.');
            return $this->redirectToRoute('admin_wallet_topup_index');
        }

        if (!$topupRequest->isPending()) {
            $this->addFlash('warning', 'Bu so\'rov allaqachon ko\'rib chiqilgan.');
            return $this->redirectToRoute('admin_wallet_topup_index');
        }

        $reason = trim($request->request->get('note', 'To\'lov tasdiqlanmadi'));

        $topupRequest->setStatus(WalletTopupRequest::STATUS_REJECTED);
        $topupRequest->setProcessedAt(new \DateTimeImmutable());
        $topupRequest->setAdminNote($reason);

        $em->flush();

        $this->addFlash('warning', 'Top-up so\'rovi rad etildi.');
        return $this->redirectToRoute('admin_wallet_topup_index');
    }
}
