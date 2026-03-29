<?php

namespace App\Controller;

use App\Service\BonusService;
use App\Service\MembershipService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/profil')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('', name: 'app_profile_index')]
    public function index(
        BonusService $bonusService,
        MembershipService $membershipService,
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $plan = $membershipService->getUserPlan($user);
        $balance = $bonusService->getBalance($user);

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'plan' => $plan,
            'balance' => $balance,
        ]);
    }

    #[Route('/tahrirlash', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('profile_edit', $request->request->get('_token'))) {
                $this->addFlash('error', 'Xavfsizlik xatosi.');
                return $this->redirectToRoute('app_profile_edit');
            }

            $fullName = trim($request->request->get('full_name', ''));
            $phone = trim($request->request->get('phone', ''));

            $errors = $validator->validate($fullName, [
                new Assert\NotBlank(message: 'Ism va familiya kiritilishi shart.'),
                new Assert\Length(min: 2, max: 100, minMessage: 'Kamida 2 ta belgi.'),
            ]);

            $phoneErrors = $validator->validate($phone, [
                new Assert\NotBlank(message: 'Telefon raqam kiritilishi shart.'),
                new Assert\Regex(pattern: '/^\+998\d{9}$/', message: 'Telefon raqam +998XXXXXXXXX formatida bo\'lishi kerak.'),
            ]);

            if (count($errors) > 0) {
                $this->addFlash('error', $errors[0]->getMessage());
                return $this->redirectToRoute('app_profile_edit');
            }

            if (count($phoneErrors) > 0) {
                $this->addFlash('error', $phoneErrors[0]->getMessage());
                return $this->redirectToRoute('app_profile_edit');
            }

            // Telefon raqam boshqa userda mavjudligini tekshirish
            $existing = $em->getRepository(\App\Entity\User::class)->findOneBy(['phone' => $phone]);
            if ($existing && $existing->getId() !== $user->getId()) {
                $this->addFlash('error', 'Bu telefon raqam boshqa foydalanuvchida ro\'yxatdan o\'tgan.');
                return $this->redirectToRoute('app_profile_edit');
            }

            $user->setFullName($fullName);
            $user->setPhone($phone);
            $em->flush();

            $this->addFlash('success', 'Ma\'lumotlaringiz muvaffaqiyatli yangilandi.');
            return $this->redirectToRoute('app_profile_index');
        }

        return $this->render('profile/edit.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/parol', name: 'app_profile_password', methods: ['GET', 'POST'])]
    public function password(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('change_password', $request->request->get('_token'))) {
                $this->addFlash('error', 'Xavfsizlik xatosi.');
                return $this->redirectToRoute('app_profile_password');
            }

            $currentPassword = $request->request->get('current_password', '');
            $newPassword = $request->request->get('new_password', '');
            $confirmPassword = $request->request->get('confirm_password', '');

            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Joriy parol noto\'g\'ri.');
                return $this->redirectToRoute('app_profile_password');
            }

            if (strlen($newPassword) < 6) {
                $this->addFlash('error', 'Yangi parol kamida 6 ta belgidan iborat bo\'lishi kerak.');
                return $this->redirectToRoute('app_profile_password');
            }

            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'Yangi parollar mos kelmadi.');
                return $this->redirectToRoute('app_profile_password');
            }

            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            $em->flush();

            $this->addFlash('success', 'Parolingiz muvaffaqiyatli o\'zgartirildi.');
            return $this->redirectToRoute('app_profile_index');
        }

        return $this->render('profile/password.html.twig', [
            'user' => $user,
        ]);
    }
}
