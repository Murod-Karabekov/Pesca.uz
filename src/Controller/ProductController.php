<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/collection', name: 'app_product_')]
class ProductController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(Request $request, ProductRepository $productRepository, CategoryRepository $categoryRepository): Response
    {
        $categorySlug = $request->query->get('category');
        $category = null;

        if ($categorySlug) {
            $category = $categoryRepository->findBySlug($categorySlug);
        }

        if ($category) {
            $products = $productRepository->findActiveByCategory($category);
        } else {
            $products = $productRepository->findActive();
        }

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'categories' => $categoryRepository->findAllOrdered(),
            'currentCategory' => $category,
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'])]
    public function show(Product $product): Response
    {
        if (!$product->isStatus()) {
            throw $this->createNotFoundException('Mahsulot topilmadi.');
        }

        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }
}
