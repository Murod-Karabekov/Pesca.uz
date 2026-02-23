<?php

namespace App\Controller\Admin;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users', name: 'admin_user_')]
#[IsGranted('ROLE_ADMIN')]
class UserManagementController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('admin/user/index.html.twig', [
            'users' => $userRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/assign-role', name: 'assign_role', methods: ['POST'])]
    public function assignRole(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isCsrfTokenValid('assign_role', $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('admin_user_index');
        }

        $phone = $request->request->get('phone');
        $role = $request->request->get('role');

        if (!in_array($role, ['ROLE_USER', 'ROLE_ADMIN'])) {
            $this->addFlash('error', 'Invalid role.');
            return $this->redirectToRoute('admin_user_index');
        }

        $user = $userRepository->findOneByPhone($phone);
        if (!$user) {
            $this->addFlash('error', 'User with phone "' . $phone . '" not found.');
            return $this->redirectToRoute('admin_user_index');
        }

        if ($role === 'ROLE_ADMIN') {
            $user->setRoles(['ROLE_ADMIN']);
        } else {
            $user->setRoles(['ROLE_USER']);
        }

        $em->flush();

        $this->addFlash('success', 'Role updated for ' . $user->getFullName() . '.');
        return $this->redirectToRoute('admin_user_index');
    }
}
