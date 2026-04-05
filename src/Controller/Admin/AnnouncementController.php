<?php

namespace App\Controller\Admin;

use App\Entity\Announcement;
use App\Form\AnnouncementType;
use App\Repository\AnnouncementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/announcements', name: 'admin_announcement_')]
#[IsGranted('ROLE_ADMIN')]
class AnnouncementController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(AnnouncementRepository $announcementRepository): Response
    {
        return $this->render('admin/announcement/index.html.twig', [
            'announcements' => $announcementRepository->findAllOrdered(),
        ]);
    }

    #[Route('/new', name: 'new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $announcement = new Announcement();
        $form = $this->createForm(AnnouncementType::class, $announcement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($announcement);
            $em->flush();

            $this->addFlash('success', 'E\'lon muvaffaqiyatli yaratildi.');
            return $this->redirectToRoute('admin_announcement_index');
        }

        return $this->render('admin/announcement/form.html.twig', [
            'form' => $form->createView(),
            'announcement' => $announcement,
            'title' => 'Yangi e\'lon qo\'shish',
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\\d+'])]
    public function edit(Announcement $announcement, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(AnnouncementType::class, $announcement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'E\'lon yangilandi.');
            return $this->redirectToRoute('admin_announcement_index');
        }

        return $this->render('admin/announcement/form.html.twig', [
            'form' => $form->createView(),
            'announcement' => $announcement,
            'title' => 'E\'lonni tahrirlash',
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function delete(Announcement $announcement, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_announcement_' . $announcement->getId(), $request->request->get('_token'))) {
            $em->remove($announcement);
            $em->flush();
            $this->addFlash('success', 'E\'lon o\'chirildi.');
        }

        return $this->redirectToRoute('admin_announcement_index');
    }
}
