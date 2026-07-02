<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordType;
use App\Form\ProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profil')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('', name: 'app_profile', methods: ['GET'])]
    public function show(): Response
    {
        return $this->render('profile/show.html.twig');
    }

    #[Route('/modifier', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Votre profil a été mis à jour.');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/mot-de-passe', name: 'app_profile_password', methods: ['GET', 'POST'])]
    public function changePassword(Request $request, UserPasswordHasherInterface $hasher, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentPassword = $form->get('currentPassword')->getData();

            if (!$hasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('danger', 'Le mot de passe actuel est incorrect.');

                return $this->redirectToRoute('app_profile_password');
            }

            $newPassword = $form->get('newPassword')->getData();
            $user->setPassword($hasher->hashPassword($user, $newPassword));
            $em->flush();

            $this->addFlash('success', 'Votre mot de passe a été modifié.');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/password.html.twig', [
            'form' => $form,
        ]);
    }
}
