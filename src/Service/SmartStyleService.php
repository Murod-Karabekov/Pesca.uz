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
        $gender = $profile->getGender();
        $occasion = $profile->getOccasion();
        $bodyType = $profile->getBodyType();
        $season = $profile->getSeason();
        $products = $this->productRepository->findActive();

        $recommendations = [];

        foreach ($products as $product) {
            // Gender filter: faqat mos jins yoki unisex mahsulotlar
            $productGender = $product->getGender();
            if ($gender && $productGender && $productGender !== 'unisex' && $productGender !== $gender) {
                continue;
            }

            $score = $product->getMatchScore($skinTone, $faceShape, $occasion, $bodyType, $season);

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
     * O'lchovlar asosida tana turini aniqlash.
     * Ko'krak, bel, son majburiy. Yelka ixtiyoriy (inverted_triangle uchun).
     * Erkaklar uchun: hourglass→hourglass(trapezoid), pear→pear(triangle), apple→apple(oval)
     *
     * @return string|null  'hourglass'|'pear'|'apple'|'rectangle'|'inverted_triangle'|null
     */
    public static function detectBodyType(
        ?int $shoulderCm,
        ?int $chestCm,
        ?int $waistCm,
        ?int $hipCm,
        ?string $gender = null,
    ): ?string {
        if ($chestCm === null || $waistCm === null || $hipCm === null) {
            return null;
        }

        // Erkaklar uchun yelka ≥ son bo'lsa ham inverted_triangle ehtimoli yuqori
        $maleShoulderThreshold = $gender === 'male' ? 2 : 4;

        // Inverted triangle: yelka sondan ancha keng
        if ($shoulderCm !== null && $shoulderCm > $hipCm + $maleShoulderThreshold) {
            return 'inverted_triangle';
        }

        $avgChestHip  = ($chestCm + $hipCm) / 2;
        $waistRatio   = $waistCm / $avgChestHip;

        // Hourglass (erkakda: trapezoid): ko'krak ≈ son (≤5 sm farq) VA bel ancha kichik (≤75%)
        if (abs($chestCm - $hipCm) <= 5 && $waistRatio <= 0.75) {
            return 'hourglass';
        }

        // Pear (erkakda: triangle): son ko'krakdan >3 sm katta
        if ($hipCm > $chestCm + 3) {
            return 'pear';
        }

        // Apple (erkakda: oval): bel son yoki ko'krakning ≥90%
        if ($waistCm >= $hipCm * 0.90 || $waistCm >= $chestCm * 0.90) {
            return 'apple';
        }

        // Default: rectangle
        return 'rectangle';
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
