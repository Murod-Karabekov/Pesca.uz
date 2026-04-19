<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\UserProfile;
use Doctrine\ORM\EntityManagerInterface;

/**
 * SmartStyle tahlil natijasini UserProfile ga yozish (veb va mobil API).
 */
class SmartStyleProfileApplier
{
    public function applyToUser(
        User $user,
        EntityManagerInterface $em,
        string $gender,
        string $skinTone,
        string $faceShape,
        ?string $occasion,
        ?string $styleIntent,
        ?string $season,
        ?int $heightCm,
        ?int $shoulderCm,
        ?int $chestCm,
        ?int $waistCm,
        ?int $hipCm,
    ): UserProfile {
        $profile = $user->getProfile();
        if (!$profile) {
            $profile = new UserProfile();
            $profile->setUser($user);
            $user->setProfile($profile);
        }

        $profile->setSkinTone($skinTone);
        $profile->setFaceShape($faceShape);
        $profile->setGender($gender);
        $profile->setOccasion($occasion !== null && $occasion !== '' ? $occasion : null);
        $profile->setStyleIntent($styleIntent !== null && $styleIntent !== '' ? $styleIntent : null);
        $profile->setSeason($season !== null && $season !== '' ? $season : null);
        $profile->setHeightCm($heightCm);
        $profile->setShoulderCm($shoulderCm);
        $profile->setChestCm($chestCm);
        $profile->setWaistCm($waistCm);
        $profile->setHipCm($hipCm);

        $bodyType = SmartStyleService::detectBodyType($shoulderCm, $chestCm, $waistCm, $hipCm, $gender);
        $profile->setBodyType($bodyType);
        $profile->setAnalyzedAt(new \DateTimeImmutable());

        $em->persist($profile);

        return $profile;
    }
}
