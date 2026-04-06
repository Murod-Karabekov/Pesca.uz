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
        $gender = $request->query->get('gender');
        $allowedGenders = ['male', 'female'];
        $currentGender = in_array($gender, $allowedGenders, true) ? $gender : null;
        $category = null;

        if ($categorySlug) {
            $category = $categoryRepository->findBySlug($categorySlug);
        }

        $products = $productRepository->findActiveByFilters($category, $currentGender);

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'categories' => $categoryRepository->findAllOrdered(),
            'currentCategory' => $category,
            'currentGender' => $currentGender,
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
