<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/collection', name: 'app_product_')]
class ProductController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(Request $request, ProductRepository $productRepository): Response
    {
        $size = $request->query->get('size');

        if ($size && in_array($size, Product::SIZES)) {
            $products = $productRepository->findActiveBySize($size);
        } else {
            $products = $productRepository->findActive();
            $size = null;
        }

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'sizes' => Product::SIZES,
            'currentSize' => $size,
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'])]
    public function show(Product $product): Response
    {
        if (!$product->isStatus()) {
            throw $this->createNotFoundException('Product not found.');
        }

        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }
}
