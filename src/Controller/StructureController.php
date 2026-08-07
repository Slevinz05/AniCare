<?php

namespace App\Controller;

use App\Entity\Structure;
use App\Entity\StructureMembership;
use App\Entity\User;
use App\Form\StructureType;
use App\Repository\AnimalRepository;
use App\Repository\StructureMembershipRepository;
use App\Repository\StructureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/structures')]
#[IsGranted('ROLE_USER')]
final class StructureController extends AbstractController
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/var/uploads/structures')] private readonly string $uploadsDir,
        private readonly SluggerInterface $slugger,
    ) {
    }
    #[Route('', name: 'app_structure_index', methods: ['GET'])]
    public function index(Request $request, StructureRepository $structureRepository, StructureMembershipRepository $membershipRepo): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($this->isGranted('ROLE_STRUCTURE')) {
            $membership = $membershipRepo->findOneBy(['user' => $user, 'role' => StructureMembership::ROLE_MANAGER]);
            if ($membership) {
                return $this->redirectToRoute('app_structure_show', ['id' => $membership->getStructure()->getId()]);
            }
        }

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
            $this->handleCoverUpload($form, $structure);

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
            $this->handleCoverUpload($form, $structure);

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

    #[Route('/{id}/cover/{fileName}', name: 'app_structure_cover', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function cover(Structure $structure, string $fileName): BinaryFileResponse
    {
        if ($structure->getCoverPhoto() !== $fileName) {
            throw $this->createNotFoundException();
        }

        $filePath = $this->uploadsDir . '/' . basename($fileName);
        if (!is_file($filePath)) {
            throw $this->createNotFoundException();
        }

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $fileName);

        return $response;
    }

    private function handleCoverUpload($form, Structure $structure): void
    {
        $file = $form->get('coverPhotoFile')->getData();
        if (!$file) {
            return;
        }

        if (!is_dir($this->uploadsDir)) {
            mkdir($this->uploadsDir, 0777, true);
        }

        if ($structure->getCoverPhoto()) {
            $oldPath = $this->uploadsDir . '/' . $structure->getCoverPhoto();
            if (is_file($oldPath)) {
                unlink($oldPath);
            }
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        $file->move($this->uploadsDir, $newFilename);
        $structure->setCoverPhoto($newFilename);
    }
}
