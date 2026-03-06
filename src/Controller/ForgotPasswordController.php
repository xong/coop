<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class ForgotPasswordController extends AbstractController
{
    // Simple password reset using token stored temporarily
    // In production, use symfonycasts/reset-password-bundle properly configured

    #[Route('/forgot-password', name: 'app_forgot_password_request')]
    public function request(
        Request $request,
        UserRepository $userRepo,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        $submitted = false;
        if ($request->isMethod('POST')) {
            $submitted = true;
            // Just show confirmation - email sending requires mailer config
        }

        return $this->render('auth/forgot_password.html.twig', [
            'submitted' => $submitted,
        ]);
    }
}
