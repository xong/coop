<?php

namespace App\Controller;

use App\Form\ChangePasswordFormType;
use App\Form\ProfileFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[IsGranted('ROLE_USER')]
#[Route('/profile')]
class ProfileController extends AbstractController
{
    #[Route('', name: 'app_profile')]
    public function show(): Response
    {
        return $this->render('profile/show.html.twig');
    }

    #[Route('/edit', name: 'app_profile_edit')]
    public function edit(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
    ): Response {
        $user = $this->getUser();
        $form = $this->createForm(ProfileFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $avatarFile = $form->get('avatarFile')->getData();
            if ($avatarFile) {
                $originalFilename = pathinfo($avatarFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $avatarFile->guessExtension();

                try {
                    $avatarDir = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars';
                    if (!is_dir($avatarDir)) {
                        mkdir($avatarDir, 0755, true);
                    }
                    $avatarFile->move($avatarDir, $newFilename);
                    $user->setAvatar('uploads/avatars/' . $newFilename);
                } catch (FileException) {
                    $this->addFlash('error', 'Fehler beim Hochladen des Profilbilds.');
                }
            }

            $em->flush();
            $this->addFlash('success', 'Profil erfolgreich aktualisiert.');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/edit.html.twig', ['form' => $form]);
    }

    #[Route('/change-password', name: 'app_profile_change_password', methods: ['GET', 'POST'])]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em,
    ): Response {
        $user = $this->getUser();
        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$hasher->isPasswordValid($user, $form->get('currentPassword')->getData())) {
                $this->addFlash('error', 'Das aktuelle Passwort ist falsch.');
            } else {
                $user->setPassword($hasher->hashPassword($user, $form->get('newPassword')->getData()));
                $em->flush();
                $this->addFlash('success', 'Passwort erfolgreich geandert.');
                return $this->redirectToRoute('app_profile');
            }
        }

        return $this->render('profile/change_password.html.twig', ['form' => $form]);
    }
}
