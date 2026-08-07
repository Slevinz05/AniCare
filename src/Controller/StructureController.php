<?php

namespace App\Controller;

use App\Entity\Structure;
use App\Entity\StructureMembership;
use App\Entity\User;
use App\Form\StructureType;
use App\Repository\AnimalRepository;
use App\Repository\StructureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/structures')]
#[IsGranted('ROLE_USER')]
final class StructureController extends AbstractController
{
    #[Route('', name: 'app_structure_index', methods: ['GET'])]
    public function index(Request $request, StructureRepository $structureRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $search = trim($request->query->getString('q'));
        $allStructures = $search
            ? $structureRepository->search($search)
            : $structureRepository->findAllOrderedByName();

        $myStructureIds = array_map(
            fn (Structure $s) => $s->getId(),
            $structureRepository->findByUser($user)
        );

        return $this->render('structure/index.html.twig', [
            'structures' => $allStructures,
            'my_structure_ids' => $myStructureIds,
            'search' => $search,
        ]);
    }

    #[Route('/nouvelle', name: 'app_structure_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $structure = new Structure();
        $structure->setCreatedBy($user);

        $form = $this->createForm(StructureType::class, $structure);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($structure);

            $isManager = $request->request->getBoolean('is_manager');

            $membership = new StructureMembership();
            $membership->setUser($user);
            $membership->setStructure($structure);
            if ($isManager) {
                $membership->setRole(StructureMembership::ROLE_MANAGER);
            } else {
                $membership->setRole(
                    $this->isGranted('ROLE_PRO') ? StructureMembership::ROLE_PRO : StructureMembership::ROLE_OWNER
                );
            }
            $entityManager->persist($membership);

            $entityManager->flush();

            $this->addFlash('success', 'Structure créée avec succès.');

            return $this->redirectToRoute('app_structure_show', ['id' => $structure->getId()]);
        }

        return $this->render('structure/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_structure_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Structure $structure, AnimalRepository $animalRepository, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('STRUCTURE_VIEW', $structure);

        /** @var User $user */
        $user = $this->getUser();

        $membership = $entityManager->getRepository(StructureMembership::class)->findOneBy([
            'user' => $user,
            'structure' => $structure,
        ]);
        $isPro = $membership && $membership->getRole() === StructureMembership::ROLE_PRO;

        $animals = $isPro
            ? $animalRepository->findByStructureAndPro($structure, $user)
            : $structure->getAnimals();

        $members = $isPro
            ? array_filter($structure->getMemberships()->toArray(), fn (StructureMembership $m) => $m->getRole() !== StructureMembership::ROLE_PRO || $m->getUser() === $user)
            : $structure->getMemberships();

        return $this->render('structure/show.html.twig', [
            'structure' => $structure,
            'animals' => $animals,
            'members' => $members,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_structure_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Structure $structure, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('STRUCTURE_EDIT', $structure);

        $form = $this->createForm(StructureType::class, $structure);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Structure mise à jour.');

            return $this->redirectToRoute('app_structure_show', ['id' => $structure->getId()]);
        }

        $otherMembers = $structure->getMemberships()->filter(
            fn (StructureMembership $m) => $m->getRole() !== StructureMembership::ROLE_MANAGER
        );

        return $this->render('structure/edit.html.twig', [
            'structure' => $structure,
            'form' => $form,
            'other_members' => $otherMembers,
        ]);
    }

    #[Route('/{id}/transferer-gerance', name: 'app_structure_transfer_manager', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function transferManager(Request $request, Structure $structure, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('STRUCTURE_EDIT', $structure);

        if (!$this->isCsrfTokenValid('transfer_manager' . $structure->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_structure_edit', ['id' => $structure->getId()]);
        }

        $targetUserId = $request->request->getInt('target_user_id');
        if (!$targetUserId) {
            $this->addFlash('danger', 'Veuillez sélectionner un membre.');
            return $this->redirectToRoute('app_structure_edit', ['id' => $structure->getId()]);
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $currentManagerMembership = $entityManager->getRepository(StructureMembership::class)->findOneBy([
            'user' => $currentUser,
            'structure' => $structure,
        ]);

        $targetMembership = $entityManager->getRepository(StructureMembership::class)->findOneBy([
            'user' => $targetUserId,
            'structure' => $structure,
        ]);

        if (!$targetMembership) {
            $this->addFlash('danger', 'Ce membre n\'appartient pas à cette structure.');
            return $this->redirectToRoute('app_structure_edit', ['id' => $structure->getId()]);
        }

        $previousRole = $targetMembership->getRole();
        $targetMembership->setRole(StructureMembership::ROLE_MANAGER);
        $currentManagerMembership->setRole($previousRole);

        $entityManager->flush();

        $this->addFlash('success', 'La gérance a été transférée à ' . $targetMembership->getUser()->getFirstName() . ' ' . $targetMembership->getUser()->getLastName() . '.');

        return $this->redirectToRoute('app_structure_show', ['id' => $structure->getId()]);
    }

    #[Route('/{id}/supprimer', name: 'app_structure_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Structure $structure, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('STRUCTURE_EDIT', $structure);

        if ($this->isCsrfTokenValid('delete' . $structure->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($structure);
            $entityManager->flush();

            $this->addFlash('success', 'Structure supprimée.');
        }

        return $this->redirectToRoute('app_structure_index');
    }

    #[Route('/rejoindre', name: 'app_structure_claim', methods: ['GET', 'POST'])]
    public function claim(Request $request, StructureRepository $structureRepository, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $code = strtoupper(trim($request->request->getString('claim_code')));

            if (!$this->isCsrfTokenValid('claim_structure', $request->request->get('_token'))) {
                $this->addFlash('danger', 'Token CSRF invalide.');
                return $this->redirectToRoute('app_structure_claim');
            }

            if (!$code) {
                $this->addFlash('danger', 'Veuillez saisir un code de structure.');
                return $this->redirectToRoute('app_structure_claim');
            }

            $structure = $structureRepository->findByClaimCode($code);
            if (!$structure) {
                $this->addFlash('danger', 'Code de structure introuvable.');
                return $this->redirectToRoute('app_structure_claim');
            }

            if ($structure->hasManager()) {
                $this->addFlash('warning', 'Cette structure a déjà un gérant.');
                return $this->redirectToRoute('app_structure_claim');
            }

            $existingMembership = $entityManager->getRepository(StructureMembership::class)->findOneBy([
                'user' => $user,
                'structure' => $structure,
            ]);

            if ($existingMembership) {
                $existingMembership->setRole(StructureMembership::ROLE_MANAGER);
            } else {
                $membership = new StructureMembership();
                $membership->setUser($user);
                $membership->setStructure($structure);
                $membership->setRole(StructureMembership::ROLE_MANAGER);
                $entityManager->persist($membership);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Vous êtes maintenant gérant de "' . $structure->getName() . '".');

            return $this->redirectToRoute('app_structure_show', ['id' => $structure->getId()]);
        }

        return $this->render('structure/claim.html.twig');
    }
}
