<?php

namespace App\Controller;

use App\Entity\AnimalReferent;
use App\Entity\User;
use App\Repository\AnimalReferentRepository;
use App\Repository\AnimalRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/referents')]
#[IsGranted('ROLE_USER')]
final class ReferentController extends AbstractController
{
    #[Route('/invitations', name: 'app_referent_invitations', methods: ['GET'])]
    public function invitations(AnimalReferentRepository $referentRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('referent/invitations.html.twig', [
            'invitations' => $referentRepository->findPendingForUser($user),
        ]);
    }

    #[Route('/inviter/{animalId}', name: 'app_referent_invite', requirements: ['animalId' => '\d+'], methods: ['GET', 'POST'])]
    public function invite(
        int $animalId,
        Request $request,
        AnimalRepository $animalRepository,
        UserRepository $userRepository,
        AnimalReferentRepository $referentRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $animal = $animalRepository->find($animalId);
        if (!$animal) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted('ANIMAL_EDIT', $animal);

        /** @var User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('invite_referent', $request->request->get('_token'))) {
                $this->addFlash('danger', 'Token CSRF invalide.');
                return $this->redirectToRoute('app_referent_invite', ['animalId' => $animalId]);
            }

            $email = trim($request->request->get('email', ''));
            $role = $request->request->get('role', AnimalReferent::ROLE_CAVALIER);

            if (!$email) {
                $this->addFlash('danger', 'Veuillez saisir un email.');
                return $this->redirectToRoute('app_referent_invite', ['animalId' => $animalId]);
            }

            if (!in_array($role, AnimalReferent::ROLES, true)) {
                $role = AnimalReferent::ROLE_AUTRE;
            }

            $invitedUser = $userRepository->findOneBy(['email' => $email]);
            if (!$invitedUser) {
                $this->addFlash('danger', 'Aucun utilisateur trouvé avec cet email. La personne doit d\'abord créer un compte AniCare.');
                return $this->redirectToRoute('app_referent_invite', ['animalId' => $animalId]);
            }

            if ($invitedUser === $user) {
                $this->addFlash('warning', 'Vous ne pouvez pas vous inviter vous-même.');
                return $this->redirectToRoute('app_referent_invite', ['animalId' => $animalId]);
            }

            if ($referentRepository->hasExistingRelation($animal, $invitedUser)) {
                $this->addFlash('warning', 'Cette personne est déjà référent ou a une invitation en attente pour cet animal.');
                return $this->redirectToRoute('app_referent_invite', ['animalId' => $animalId]);
            }

            $referent = new AnimalReferent();
            $referent->setAnimal($animal);
            $referent->setUser($invitedUser);
            $referent->setType(AnimalReferent::TYPE_SECONDAIRE);
            $referent->setRole($role);
            $referent->setStatus(AnimalReferent::STATUS_PENDING);
            $referent->setDesignatedBy($user);

            $entityManager->persist($referent);
            $entityManager->flush();

            $this->addFlash('success', sprintf(
                'Invitation envoyée à %s en tant que %s.',
                $invitedUser->getFullName(),
                $referent->getRoleLabel()
            ));

            return $this->redirectToRoute('app_animal_show', ['slug' => $animal->getSlug()]);
        }

        return $this->render('referent/invite.html.twig', [
            'animal' => $animal,
            'roles' => [
                'Cavalier' => AnimalReferent::ROLE_CAVALIER,
                'Entraîneur' => AnimalReferent::ROLE_ENTRAINEUR,
                'Gérant' => AnimalReferent::ROLE_GERANT,
                'Groom' => AnimalReferent::ROLE_GROOM,
                'Éleveur' => AnimalReferent::ROLE_ELEVEUR,
                'Autre' => AnimalReferent::ROLE_AUTRE,
            ],
        ]);
    }

    #[Route('/recherche-utilisateur', name: 'app_referent_search_user', methods: ['GET'])]
    public function searchUser(Request $request, UserRepository $userRepository): JsonResponse
    {
        $query = trim($request->query->get('q', ''));
        if (strlen($query) < 2) {
            return $this->json([]);
        }

        $users = $userRepository->createQueryBuilder('u')
            ->where('u.firstName LIKE :q OR u.lastName LIKE :q OR u.email LIKE :q')
            ->setParameter('q', '%' . $query . '%')
            ->setMaxResults(8)
            ->getQuery()
            ->getResult();

        $results = [];
        foreach ($users as $u) {
            if ($u === $this->getUser()) {
                continue;
            }
            $results[] = [
                'id' => $u->getId(),
                'name' => $u->getFullName(),
                'email' => $u->getEmail(),
                'type' => $u->getAccountType(),
            ];
        }

        return $this->json($results);
    }

    #[Route('/{id}/accepter', name: 'app_referent_accept', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function accept(AnimalReferent $referent, Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($referent->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if ($referent->getStatus() !== AnimalReferent::STATUS_PENDING) {
            $this->addFlash('warning', 'Cette invitation n\'est plus en attente.');
            return $this->redirectToRoute('app_referent_invitations');
        }

        if ($this->isCsrfTokenValid('accept' . $referent->getId(), $request->request->get('_token'))) {
            $referent->setStatus(AnimalReferent::STATUS_ACTIVE);
            $entityManager->flush();

            $this->addFlash('success', sprintf(
                'Vous êtes maintenant référent de %s.',
                $referent->getAnimal()->getName()
            ));
        }

        return $this->redirectToRoute('app_referent_invitations');
    }

    #[Route('/{id}/refuser', name: 'app_referent_refuse', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function refuse(AnimalReferent $referent, Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($referent->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if ($referent->getStatus() !== AnimalReferent::STATUS_PENDING) {
            $this->addFlash('warning', 'Cette invitation n\'est plus en attente.');
            return $this->redirectToRoute('app_referent_invitations');
        }

        if ($this->isCsrfTokenValid('refuse' . $referent->getId(), $request->request->get('_token'))) {
            $referent->setStatus(AnimalReferent::STATUS_REFUSED);
            $entityManager->flush();

            $this->addFlash('info', 'Invitation refusée.');
        }

        return $this->redirectToRoute('app_referent_invitations');
    }

    #[Route('/{id}/revoquer', name: 'app_referent_revoke', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function revoke(AnimalReferent $referent, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ANIMAL_EDIT', $referent->getAnimal());

        if ($referent->isPrincipal()) {
            $this->addFlash('danger', 'Le référent principal ne peut pas être révoqué. Utilisez le transfert de principal.');
            return $this->redirectToRoute('app_animal_show', ['slug' => $referent->getAnimal()->getSlug()]);
        }

        if ($this->isCsrfTokenValid('revoke' . $referent->getId(), $request->request->get('_token'))) {
            $referent->revoke();
            $entityManager->flush();

            $this->addFlash('success', sprintf(
                '%s a été révoqué comme référent.',
                $referent->getUser()->getFullName()
            ));
        }

        return $this->redirectToRoute('app_animal_show', ['slug' => $referent->getAnimal()->getSlug()]);
    }

    #[Route('/transfert/{animalId}', name: 'app_referent_transfer', requirements: ['animalId' => '\d+'], methods: ['GET', 'POST'])]
    public function transfer(
        int $animalId,
        Request $request,
        AnimalRepository $animalRepository,
        UserRepository $userRepository,
        AnimalReferentRepository $referentRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $animal = $animalRepository->find($animalId);
        if (!$animal) {
            throw $this->createNotFoundException();
        }

        /** @var User $user */
        $user = $this->getUser();

        $currentPrincipal = $referentRepository->findPrincipal($animal);
        $isPrincipal = $currentPrincipal && $currentPrincipal->getUser() === $user;
        $isOwnerLegacy = $animal->getOwner() === $user && !$currentPrincipal;

        if (!$isPrincipal && !$isOwnerLegacy) {
            throw $this->createAccessDeniedException('Seul le référent principal peut proposer un transfert.');
        }

        $pendingTransfer = $referentRepository->findPendingTransfer($animal);

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('transfer_principal', $request->request->get('_token'))) {
                $this->addFlash('danger', 'Token CSRF invalide.');
                return $this->redirectToRoute('app_referent_transfer', ['animalId' => $animalId]);
            }

            if ($pendingTransfer) {
                $this->addFlash('warning', 'Un transfert est déjà en attente pour cet animal.');
                return $this->redirectToRoute('app_referent_transfer', ['animalId' => $animalId]);
            }

            $email = trim($request->request->get('email', ''));
            if (!$email) {
                $this->addFlash('danger', 'Veuillez saisir un email.');
                return $this->redirectToRoute('app_referent_transfer', ['animalId' => $animalId]);
            }

            $successor = $userRepository->findOneBy(['email' => $email]);
            if (!$successor) {
                $this->addFlash('danger', 'Aucun utilisateur trouvé avec cet email.');
                return $this->redirectToRoute('app_referent_transfer', ['animalId' => $animalId]);
            }

            if ($successor === $user) {
                $this->addFlash('warning', 'Vous êtes déjà le référent principal.');
                return $this->redirectToRoute('app_referent_transfer', ['animalId' => $animalId]);
            }

            $transferInvitation = new AnimalReferent();
            $transferInvitation->setAnimal($animal);
            $transferInvitation->setUser($successor);
            $transferInvitation->setType(AnimalReferent::TYPE_PRINCIPAL);
            $transferInvitation->setRole($request->request->get('role', AnimalReferent::ROLE_PROPRIETAIRE));
            $transferInvitation->setStatus(AnimalReferent::STATUS_PENDING);
            $transferInvitation->setDesignatedBy($user);

            $entityManager->persist($transferInvitation);
            $entityManager->flush();

            $this->addFlash('success', sprintf(
                'Proposition de transfert envoyée à %s. Le transfert sera effectif après son acceptation.',
                $successor->getFullName()
            ));

            return $this->redirectToRoute('app_animal_show', ['slug' => $animal->getSlug()]);
        }

        return $this->render('referent/transfer.html.twig', [
            'animal' => $animal,
            'pendingTransfer' => $pendingTransfer,
            'roles' => [
                'Propriétaire' => AnimalReferent::ROLE_PROPRIETAIRE,
                'Cavalier' => AnimalReferent::ROLE_CAVALIER,
                'Entraîneur' => AnimalReferent::ROLE_ENTRAINEUR,
                'Gérant' => AnimalReferent::ROLE_GERANT,
                'Éleveur' => AnimalReferent::ROLE_ELEVEUR,
                'Autre' => AnimalReferent::ROLE_AUTRE,
            ],
        ]);
    }

    #[Route('/{id}/accepter-transfert', name: 'app_referent_accept_transfer', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function acceptTransfer(
        AnimalReferent $referent,
        Request $request,
        AnimalReferentRepository $referentRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($referent->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if ($referent->getStatus() !== AnimalReferent::STATUS_PENDING || !$referent->isPrincipal()) {
            $this->addFlash('warning', 'Cette proposition de transfert n\'est plus valide.');
            return $this->redirectToRoute('app_referent_invitations');
        }

        if (!$this->isCsrfTokenValid('accept_transfer' . $referent->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_referent_invitations');
        }

        $animal = $referent->getAnimal();
        $oldPrincipal = $referentRepository->findPrincipal($animal);

        if ($oldPrincipal) {
            $oldPrincipal->setType(AnimalReferent::TYPE_SECONDAIRE);
        }

        $referent->setStatus(AnimalReferent::STATUS_ACTIVE);
        $entityManager->flush();

        $this->addFlash('success', sprintf(
            'Vous êtes maintenant référent principal de %s.',
            $animal->getName()
        ));

        return $this->redirectToRoute('app_animal_show', ['slug' => $animal->getSlug()]);
    }

    #[Route('/{id}/annuler-transfert', name: 'app_referent_cancel_transfer', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function cancelTransfer(
        AnimalReferent $referent,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $isDesignator = $referent->getDesignatedBy() === $user;
        $isRecipient = $referent->getUser() === $user;

        if (!$isDesignator && !$isRecipient) {
            throw $this->createAccessDeniedException();
        }

        if ($referent->getStatus() !== AnimalReferent::STATUS_PENDING || !$referent->isPrincipal()) {
            $this->addFlash('warning', 'Cette proposition de transfert n\'est plus valide.');
            return $this->redirectToRoute('app_referent_invitations');
        }

        if (!$this->isCsrfTokenValid('cancel_transfer' . $referent->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_referent_invitations');
        }

        $referent->setStatus(AnimalReferent::STATUS_REFUSED);
        $entityManager->flush();

        $this->addFlash('info', 'Proposition de transfert annulée.');

        if ($isDesignator) {
            return $this->redirectToRoute('app_animal_show', ['slug' => $referent->getAnimal()->getSlug()]);
        }

        return $this->redirectToRoute('app_referent_invitations');
    }
}
