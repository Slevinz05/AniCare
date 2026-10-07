<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\FormLoginAuthenticator;

class ActivationController extends AbstractController
{
    #[Route('/activation/{token}', name: 'app_activation')]
    public function activate(
        string $token,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $user = $em->getRepository(User::class)->findOneBy(['activationToken' => $token]);

        if (!$user) {
            $this->addFlash('danger', 'Ce lien d\'activation est invalide ou a déjà été utilisé.');
            return $this->redirectToRoute('app_login');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $password = $request->request->get('password', '');
            $passwordConfirm = $request->request->get('password_confirm', '');

            if (strlen($password) < 8) {
                $error = 'Le mot de passe doit contenir au moins 8 caractères.';
            } elseif ($password !== $passwordConfirm) {
                $error = 'Les mots de passe ne correspondent pas.';
            } else {
                $user->setPassword($passwordHasher->hashPassword($user, $password));
                $user->setActivationToken(null);
                $em->flush();

                $this->addFlash('success', 'Votre compte est activé ! Connectez-vous avec votre nouveau mot de passe.');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/activation.html.twig', [
            'user' => $user,
            'error' => $error,
        ]);
    }
}
