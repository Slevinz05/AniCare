<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordType;
use App\Form\ProfileType;
use App\Service\DocumentUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
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
    public function edit(
        Request $request,
        EntityManagerInterface $em,
        DocumentUploader $documentUploader,
        #[Autowire('%kernel.project_dir%/var/uploads/profiles')] string $profileUploadsDir,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $isPro = $user->getAccountType() === 'PRO';

        $form = $this->createForm(ProfileType::class, $user, [
            'is_pro' => $isPro,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($isPro && $form->has('profilePhotosUpload')) {
                $uploadedFiles = $form->get('profilePhotosUpload')->getData();
                if (!empty($uploadedFiles)) {
                    if (!is_dir($profileUploadsDir)) {
                        mkdir($profileUploadsDir, 0775, true);
                    }
                    $photos = $documentUploader->uploadMany($uploadedFiles, $profileUploadsDir);
                    foreach ($photos as $photo) {
                        $user->addProfilePhoto($photo);
                    }
                }
            }

            $em->flush();
            $this->addFlash('success', 'Votre profil a été mis à jour.');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/photo/{fileName}/supprimer', name: 'app_profile_photo_delete', methods: ['POST'])]
    public function deletePhoto(
        Request $request,
        string $fileName,
        EntityManagerInterface $em,
        #[Autowire('%kernel.project_dir%/var/uploads/profiles')] string $profileUploadsDir,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('delete_photo' . $fileName, $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_profile_edit');
        }

        $filePath = $profileUploadsDir . '/' . basename($fileName);
        if (is_file($filePath)) {
            unlink($filePath);
        }

        $user->removeProfilePhoto($fileName);
        $em->flush();

        $this->addFlash('success', 'Photo supprimée.');
        return $this->redirectToRoute('app_profile_edit');
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
