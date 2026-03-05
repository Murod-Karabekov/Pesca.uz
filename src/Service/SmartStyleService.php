<?php

namespace App\Service;

use App\Entity\Product;
use App\Entity\UserProfile;
use App\Repository\ProductRepository;

class SmartStyleService
{
    public function __construct(
        private ProductRepository $productRepository,
    ) {}

    /**
     * Foydalanuvchi profiliga mos mahsulotlarni topish
     *
     * @return array{product: Product, score: int}[]
     */
    public function getRecommendations(UserProfile $profile, int $minScore = 50): array
    {
        if (!$profile->isAnalyzed()) {
            return [];
        }

        $skinTone = $profile->getSkinTone();
        $faceShape = $profile->getFaceShape();
        $products = $this->productRepository->findActive();

        $recommendations = [];

        foreach ($products as $product) {
            $score = $product->getMatchScore($skinTone, $faceShape);

            if ($score >= $minScore) {
                $recommendations[] = [
                    'product' => $product,
                    'score' => $score,
                ];
            }
        }

        // Score bo'yicha kamayish tartibida
        usort($recommendations, fn($a, $b) => $b['score'] <=> $a['score']);

        return $recommendations;
    }

    /**
     * Match score uchun label qaytarish
     */
    public static function getScoreLabel(int $score): string
    {
        return match (true) {
            $score >= 90 => 'Eng mos',
            $score >= 70 => 'Juda mos',
            $score >= 50 => 'Mos',
            default => '',
        };
    }

    /**
     * Match score uchun CSS class qaytarish
     */
    public static function getScoreColor(int $score): string
    {
        return match (true) {
            $score >= 90 => 'green',
            $score >= 70 => 'blue',
            $score >= 50 => 'yellow',
            default => 'gray',
        };
    }

    /**
     * Yuz shakli uchun maslahatlar
     */
    public static function getFaceShapeTips(string $faceShape): array
    {
        return match ($faceShape) {
            'oval' => [
                'V-shakl yoqali kiyimlar',
                'Yumshoq chiziqli dizaynlar',
                'Deyarli barcha fasonlar mos keladi',
            ],
            'round' => [
                'V-shakl yoqali kiyimlar yuzni cho\'zadi',
                'Vertikal chiziqli dizaynlar',
                'Uzun yoqali kiyimlar',
            ],
            'square' => [
                'Yumshoq, dumaloq yoqali kiyimlar',
                'Asimmetrik dizaynlar',
                'Yumshoq materiallar',
            ],
            'heart' => [
                'Keng yoqali kiyimlar',
                'Pastki qismida hajmli dizaynlar',
                'V-shakl va scoop yoqalar',
            ],
            'oblong' => [
                'Keng yoqali kiyimlar',
                'Gorizontal chiziqlar',
                'Qisqa yoqali dizaynlar',
            ],
            'diamond' => [
                'Keng yoqali yoki off-shoulder',
                'A-line fasondagi kiyimlar',
                'Yoqa atrofida bezakli dizaynlar',
            ],
            default => [],
        };
    }
}
