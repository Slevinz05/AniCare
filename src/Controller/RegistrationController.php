<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\AnimalReferentRepository;
use App\Security\AppAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/inscription', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        Security $security,
        EntityManagerInterface $entityManager,
        AnimalReferentRepository $referentRepository,
    ): Response {
        $invitationToken = $request->query->get('invitation');

        $user = new User();

        if ($invitationToken) {
            $pendingReferent = $referentRepository->findByToken($invitationToken);
            if ($pendingReferent && $pendingReferent->getContactEmail()) {
                $user->setEmail($pendingReferent->getContactEmail());
            }
        }

        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $accountType = $user->getAccountType();
            if ($accountType === 'PRO' || $accountType === 'BOTH') {
                $user->setRoles(['ROLE_PRO']);
            } elseif ($accountType === 'STRUCTURE') {
                $user->setRoles(['ROLE_STRUCTURE']);
            } else {
                $user->setRoles(['ROLE_USER']);
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $this->linkPendingInvitations($user, $invitationToken, $referentRepository, $entityManager);

            $response = $security->login($user, AppAuthenticator::class, 'main');

            $linkedReferent = $invitationToken ? $referentRepository->findByToken($invitationToken) : null;
            if ($linkedReferent && $linkedReferent->getUser() === $user) {
                $this->addFlash('success', sprintf(
                    'Bienvenue ! Vous êtes maintenant référent secondaire de %s.',
                    $linkedReferent->getAnimal()->getName()
                ));
                return $this->redirectToRoute('app_animal_show', ['slug' => $linkedReferent->getAnimal()->getSlug()]);
            }

            if ($accountType === 'STRUCTURE') {
                return $this->redirectToRoute('app_structure_index');
            }

            return $response ?? $this->redirectToRoute('app_home');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
            'invitationToken' => $invitationToken,
        ]);
    }

    private function linkPendingInvitations(
        User $user,
        ?string $invitationToken,
        AnimalReferentRepository $referentRepository,
        EntityManagerInterface $entityManager,
    ): void {
        if ($invitationToken) {
            $referent = $referentRepository->findByToken($invitationToken);
            if ($referent && $referent->getStatus() === 'pending' && $referent->getUser() === null) {
                $referent->setUser($user);
                $referent->setStatus('active');
                $referent->setInvitationToken(null);
            }
        }

        $pendingByEmail = $referentRepository->findPendingByEmail($user->getEmail());
        foreach ($pendingByEmail as $referent) {
            $referent->setUser($user);
            $referent->setStatus('active');
            $referent->setInvitationToken(null);
        }

        $entityManager->flush();
    }
}