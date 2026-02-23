<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/products', name: 'admin_product_')]
#[IsGranted('ROLE_ADMIN')]
class ProductCrudController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(ProductRepository $productRepository): Response
    {
        return $this->render('admin/product/index.html.twig', [
            'products' => $productRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'new')]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        $product = new Product();
        $product->setStatus(true);
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $product, $slugger, 'products');
            $em->persist($product);
            $em->flush();

            $this->addFlash('success', 'Product created successfully!');
            return $this->redirectToRoute('admin_product_index');
        }

        return $this->render('admin/product/form.html.twig', [
            'form' => $form->createView(),
            'product' => $product,
            'title' => 'Add New Product',
        ]);
    }

    #[Route('/{id}/edit', name: 'edit')]
    public function edit(
        Product $product,
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $product, $slugger, 'products');
            $em->flush();

            $this->addFlash('success', 'Product updated successfully!');
            return $this->redirectToRoute('admin_product_index');
        }

        return $this->render('admin/product/form.html.twig', [
            'form' => $form->createView(),
            'product' => $product,
            'title' => 'Edit Product',
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        Product $product,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        if ($this->isCsrfTokenValid('delete_product_' . $product->getId(), $request->request->get('_token'))) {
            // Remove image file
            if ($product->getImage()) {
                $imagePath = $this->getParameter('kernel.project_dir') . '/public/uploads/products/' . $product->getImage();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            $em->remove($product);
            $em->flush();
            $this->addFlash('success', 'Product deleted.');
        }

        return $this->redirectToRoute('admin_product_index');
    }

    #[Route('/{id}/toggle', name: 'toggle', methods: ['POST'])]
    public function toggle(
        Product $product,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        if ($this->isCsrfTokenValid('toggle_product_' . $product->getId(), $request->request->get('_token'))) {
            $product->setStatus(!$product->isStatus());
            $em->flush();
            $status = $product->isStatus() ? 'activated' : 'deactivated';
            $this->addFlash('success', "Product {$status}.");
        }

        return $this->redirectToRoute('admin_product_index');
    }

    private function handleImageUpload($form, Product $product, SluggerInterface $slugger, string $folder): void
    {
        $imageFile = $form->get('imageFile')->getData();
        if ($imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

            try {
                $imageFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/' . $folder,
                    $newFilename
                );
                // Remove old image
                if ($product->getImage()) {
                    $oldPath = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $folder . '/' . $product->getImage();
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }
                $product->setImage($newFilename);
            } catch (FileException $e) {
                $this->addFlash('error', 'Failed to upload image.');
            }
        }
    }
}
