<?php

namespace App\Controller\Admin;

use App\Entity\CorporatePartnershipRequest;
use App\Repository\CorporatePartnershipRequestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/corporate-partnerships', name: 'admin_corporate_partnership_')]
#[IsGranted('ROLE_ADMIN')]
class CorporatePartnershipController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(CorporatePartnershipRequestRepository $repo): Response
    {
        return $this->render('admin/corporate_partnership/index.html.twig', [
            'requests' => $repo->findAllRecent(),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(CorporatePartnershipRequest $corporatePartnershipRequest): Response
    {
        return $this->render('admin/corporate_partnership/show.html.twig', [
            'inquiry' => $corporatePartnershipRequest,
        ]);
    }
}
