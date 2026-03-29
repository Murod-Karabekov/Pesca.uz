<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationType;
use App\Service\ReferralService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastPhone = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_phone' => $lastPhone,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // This method is intercepted by the logout key on the firewall.
        throw new \LogicException('This method should not be reached.');
    }

    #[Route('/register', name: 'app_register')]
    #[Route('/register/{refCode}', name: 'app_register_referral')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        ReferralService $referralService,
        ?string $refCode = null
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        // Session/query param orqali ham referral code olish mumkin
        if ($refCode === null) {
            $refCode = $request->query->get('ref');
        }
        if ($refCode !== null) {
            $request->getSession()->set('referral_code', $refCode);
        }
        $refCode = $refCode ?? $request->getSession()->get('referral_code');

        // Referrer'ni topish
        $referrer = null;
        if ($refCode) {
            $referrer = $referralService->findReferrerByCode($refCode);
        }

        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
                $passwordHasher->hashPassword($user, $form->get('plainPassword')->getData())
            );
            $user->setRoles(['ROLE_USER']);

            $em->persist($user);
            $em->flush();

            // Referral bog'lanish yaratish
            if ($referrer !== null && $referrer->getId() !== $user->getId()) {
                $referralService->registerReferral($referrer, $user);
            }

            // Session'dan referral code ni o'chirish
            $request->getSession()->remove('referral_code');

            $this->addFlash('success', 'Hisob muvaffaqiyatli yaratildi! Iltimos, tizimga kiring.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig', [
            'registrationForm' => $form->createView(),
            'referrer' => $referrer,
        ]);
    }
}
