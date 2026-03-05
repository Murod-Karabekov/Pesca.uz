<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/categories', name: 'admin_category_')]
#[IsGranted('ROLE_ADMIN')]
class CategoryCrudController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(CategoryRepository $categoryRepository): Response
    {
        return $this->render('admin/category/index.html.twig', [
            'categories' => $categoryRepository->findAllOrdered(),
        ]);
    }

    #[Route('/new', name: 'new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($category);
            $em->flush();

            $this->addFlash('success', 'Kategoriya muvaffaqiyatli yaratildi!');
            return $this->redirectToRoute('admin_category_index');
        }

        return $this->render('admin/category/form.html.twig', [
            'form' => $form->createView(),
            'category' => $category,
            'title' => 'Yangi kategoriya qo\'shish',
        ]);
    }

    #[Route('/{id}/edit', name: 'edit')]
    public function edit(
        Category $category,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Kategoriya muvaffaqiyatli yangilandi!');
            return $this->redirectToRoute('admin_category_index');
        }

        return $this->render('admin/category/form.html.twig', [
            'form' => $form->createView(),
            'category' => $category,
            'title' => 'Kategoriyani tahrirlash',
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        Category $category,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        if ($this->isCsrfTokenValid('delete_category_' . $category->getId(), $request->request->get('_token'))) {
            $em->remove($category);
            $em->flush();
            $this->addFlash('success', 'Kategoriya o\'chirildi.');
        }

        return $this->redirectToRoute('admin_category_index');
    }
}
