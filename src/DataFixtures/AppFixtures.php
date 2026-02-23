<?php

namespace App\DataFixtures;

use App\Entity\Product;
use App\Entity\Tailor;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // ─── Admin User ───
        $admin = new User();
        $admin->setFullName('Admin Pesca');
        $admin->setPhone('+998901234567');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $manager->persist($admin);

        // ─── Test User ───
        $user = new User();
        $user->setFullName('Test User');
        $user->setPhone('+998901111111');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'user123'));
        $manager->persist($user);

        // ─── Sample Products ───
        $products = [
            ['name' => 'Classic White Coat', 'price' => '350000', 'description' => 'A timeless white medical coat crafted from premium cotton blend. Designed for maximum comfort during long shifts with reinforced stitching and breathable fabric.', 'size' => ['S', 'M', 'L', 'XL']],
            ['name' => 'Surgical Scrub Set', 'price' => '280000', 'description' => 'Professional surgical scrub set featuring moisture-wicking fabric and modern tailored fit. Available in our signature peach color.', 'size' => ['XS', 'S', 'M', 'L', 'XL']],
            ['name' => 'Premium Lab Coat', 'price' => '420000', 'description' => 'Our premium lab coat features Italian button closures, elegant collar design, and hidden pockets. The ultimate in medical fashion.', 'size' => ['S', 'M', 'L']],
            ['name' => 'Comfort Nursing Uniform', 'price' => '310000', 'description' => 'Designed specifically for nursing professionals. Ultra-soft fabric with stretch technology for unrestricted movement.', 'size' => ['XS', 'S', 'M', 'L', 'XL']],
            ['name' => 'Dental Professional Set', 'price' => '295000', 'description' => 'Compact and stylish dental professional wear. Stain-resistant fabric with easy-care properties.', 'size' => ['S', 'M', 'L']],
            ['name' => 'Executive Medical Blazer', 'price' => '520000', 'description' => 'For the medical professional who demands sophistication. This blazer-style coat bridges the gap between fashion and function.', 'size' => ['M', 'L', 'XL']],
        ];

        foreach ($products as $data) {
            $product = new Product();
            $product->setName($data['name']);
            $product->setPrice($data['price']);
            $product->setDescription($data['description']);
            $product->setSize($data['size']);
            $product->setStatus(true);
            $manager->persist($product);
        }

        // ─── Sample Tailors ───
        $tailors = [
            ['name' => 'Aziza Karimova', 'description' => '15 years of experience in medical clothing tailoring. Specializes in custom-fit lab coats and surgical wear. Known for impeccable attention to detail.', 'price' => '250000'],
            ['name' => 'Rustam Aliyev', 'description' => 'Master tailor with expertise in premium medical uniforms. Trained in European tailoring techniques. Creates bespoke medical wear for clinics and hospitals.', 'price' => '350000'],
            ['name' => 'Dilnoza Ergasheva', 'description' => 'Fashion-forward medical clothing designer. Combines modern aesthetics with professional requirements. Specializes in women\'s medical wear.', 'price' => '300000'],
        ];

        foreach ($tailors as $data) {
            $tailor = new Tailor();
            $tailor->setName($data['name']);
            $tailor->setDescription($data['description']);
            $tailor->setPrice($data['price']);
            $manager->persist($tailor);
        }

        $manager->flush();
    }
}
