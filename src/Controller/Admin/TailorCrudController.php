<?php

namespace App\Controller\Admin;

use App\Entity\Tailor;
use App\Form\TailorType;
use App\Repository\TailorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/tailors', name: 'admin_tailor_')]
#[IsGranted('ROLE_ADMIN')]
class TailorCrudController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(TailorRepository $tailorRepository): Response
    {
        return $this->render('admin/tailor/index.html.twig', [
            'tailors' => $tailorRepository->findAllOrdered(),
        ]);
    }

    #[Route('/new', name: 'new')]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        $tailor = new Tailor();
        $form = $this->createForm(TailorType::class, $tailor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $tailor, $slugger);
            $em->persist($tailor);
            $em->flush();

            $this->addFlash('success', 'Tikuvchi muvaffaqiyatli qo\'shildi!');
            return $this->redirectToRoute('admin_tailor_index');
        }

        return $this->render('admin/tailor/form.html.twig', [
            'form' => $form->createView(),
            'tailor' => $tailor,
            'title' => 'Yangi tikuvchi qo\'shish',
        ]);
    }

    #[Route('/{id}/edit', name: 'edit')]
    public function edit(
        Tailor $tailor,
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        $form = $this->createForm(TailorType::class, $tailor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $tailor, $slugger);
            $em->flush();

            $this->addFlash('success', 'Tikuvchi muvaffaqiyatli yangilandi!');
            return $this->redirectToRoute('admin_tailor_index');
        }

        return $this->render('admin/tailor/form.html.twig', [
            'form' => $form->createView(),
            'tailor' => $tailor,
            'title' => 'Tikuvchini tahrirlash',
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        Tailor $tailor,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        if ($this->isCsrfTokenValid('delete_tailor_' . $tailor->getId(), $request->request->get('_token'))) {
            if ($tailor->getImage() && !$tailor->isExternalImage()) {
                $imagePath = $this->getParameter('kernel.project_dir') . '/public/uploads/tailors/' . $tailor->getImage();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            $em->remove($tailor);
            $em->flush();
            $this->addFlash('success', 'Tikuvchi o\'chirildi.');
        }

        return $this->redirectToRoute('admin_tailor_index');
    }

    private function handleImageUpload($form, Tailor $tailor, SluggerInterface $slugger): void
    {
        // URL orqali rasm qo'shish (fayl yuklanmaydi, VDS'da joy olmaydi)
        $imageUrl = $form->get('imageUrl')->getData();
        if ($imageUrl) {
            // Eski lokal rasmni o'chirib tashlash
            if ($tailor->getImage() && !$tailor->isExternalImage()) {
                $oldPath = $this->getParameter('kernel.project_dir') . '/public/uploads/tailors/' . $tailor->getImage();
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }
            $tailor->setImage($imageUrl);
            return;
        }

        // Fayl yuklash (avvalgi usul)
        $imageFile = $form->get('imageFile')->getData();
        if ($imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

            try {
                $imageFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/tailors',
                    $newFilename
                );
                if ($tailor->getImage() && !$tailor->isExternalImage()) {
                    $oldPath = $this->getParameter('kernel.project_dir') . '/public/uploads/tailors/' . $tailor->getImage();
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }
                $tailor->setImage($newFilename);
            } catch (FileException $e) {
                $this->addFlash('error', 'Rasmni yuklash muvaffaqiyatsiz bo\'ldi.');
            }
        }
    }
}
