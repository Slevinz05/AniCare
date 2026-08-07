<?php

namespace App\Controller;

use App\Entity\Animal;
use App\Entity\AnimalDeletionRequest;
use App\Entity\AnimalShare;
use App\Entity\Reminder;
use App\Entity\StructureMembership;
use App\Entity\User;
use App\Form\AnimalType;
use App\Repository\AnimalDeletionRequestRepository;
use App\Repository\AnimalRepository;
use App\Repository\StructureMembershipRepository;
use App\Repository\UserRepository;
use App\Service\DocumentUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/mes-chevaux')]
#[IsGranted('ROLE_USER')]
final class AnimalController extends AbstractController
{
    public function __construct(
        private readonly DocumentUploader $documentUploader,
        private readonly SluggerInterface $slugger,

        #[Autowire('%kernel.project_dir%/var/uploads/animals')]
        private readonly string $animalUploadsDirectory,

        #[Autowire('%kernel.project_dir%/public/uploads/photos')]
        private readonly string $photoDirectory,
    ) {
    }

    #[Route('/', name: 'app_animal_index', methods: ['GET'])]
    public function index(AnimalRepository $animalRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
            $animals = $animalRepository->findAll();
        } else {
            $animals = $animalRepository->findAccessibleAnimals($user);
        }

        return $this->render('animal/index.html.twig', [
            'animals' => $animals,
        ]);
    }

    #[Route('/ajouter', name: 'app_animal_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository, StructureMembershipRepository $membershipRepo): Response
    {
        $animal = new Animal();

        /** @var User $user */
        $user = $this->getUser();

        $isStructure = $this->isGranted('ROLE_STRUCTURE');
        $structure = null;

        if ($isStructure) {
            $membership = $membershipRepo->findOneBy(['user' => $user, 'role' => StructureMembership::ROLE_MANAGER]);
            $structure = $membership?->getStructure();

            if ($structure) {
                $animal->setStructure($structure);
                $animal->setLivingPlaceName($structure->getName());
                $animal->setLivingPlaceStreet($structure->getStreet());
                $animal->setLivingPlaceComplement($structure->getComplement());
                $animal->setLivingPlacePostalCode($structure->getPostalCode());
                $animal->setLivingPlaceCity($structure->getCity());
                $animal->setLivingPlaceCountry($structure->getCountry());
                $animal->setLivingPlaceManagerLastName($user->getLastName());
                $animal->setLivingPlaceManagerFirstName($user->getFirstName());
                $animal->setLivingPlaceManagerPhone($user->getPhone() ?? $structure->getPhone());
                $animal->setLivingPlaceManagerEmail($user->getEmail() ?? $structure->getEmail());
            }
        } else {
            $animal->setTrustedContactLastName($user->getLastName());
            $animal->setTrustedContactFirstName($user->getFirstName());
            $animal->setTrustedContactPhone($user->getPhone());
        }

        $form = $this->createForm(AnimalType::class, $animal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$animal->getBirthDate() && $form->get('age')->getData() === null) {
                $this->addFlash('danger', 'Veuillez renseigner la date de naissance ou l\'age du cheval.');
                return $this->render('animal/new.html.twig', [
                    'animal' => $animal,
                    'form' => $form,
                ]);
            }

            if ($isStructure) {
                $ownerId = $request->request->get('owner_id');
                if ($ownerId) {
                    $owner = $userRepository->find($ownerId);
                    if ($owner) {
                        $animal->setOwner($owner);
                    }
                }
                if (!$animal->getOwner()) {
                    $animal->setOwner($user);
                }
                if ($structure) {
                    $animal->setStructure($structure);
                }
            } else {
                $animal->setOwner($user);
            }
            $animal->setSpecies('Cheval');

            $this->syncAgeAndBirthDate($form, $animal);

            if ($animal->getCoat() === 'Autre' && $form->get('coatCustom')->getData()) {
                $animal->setCoat($form->get('coatCustom')->getData());
            }

            $this->handlePhotoUpload($form, $animal);
            $this->handleUploadedDocuments($form, $animal);

            $entityManager->persist($animal);

            $this->handleProfessionalInvitation($request, $animal, $userRepository, $entityManager);
            $this->handleReminders($request, $animal, $user, $entityManager);

            $entityManager->flush();

            $animal->ensureSlug();
            $entityManager->flush();

            $nextAction = $request->request->get('next_action');
            if ($nextAction === 'consultation' && $this->isGranted('ROLE_PRO')) {
                return $this->redirectToRoute('app_health_book_entry_new', [
                    'animal' => $animal->getId(),
                ], Response::HTTP_SEE_OTHER);
            }
            if ($nextAction === 'appointment' && $this->isGranted('ROLE_PRO')) {
                return $this->redirectToRoute('app_appointment_new', [
                    'animal' => $animal->getId(),
                ], Response::HTTP_SEE_OTHER);
            }

            return $this->redirectToRoute('app_animal_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('animal/new.html.twig', [
            'animal' => $animal,
            'form' => $form,
        ]);
    }

    #[Route('/{slug}', name: 'app_animal_show', methods: ['GET'])]
    public function show(string $slug, AnimalRepository $animalRepository, UserRepository $userRepository, AnimalDeletionRequestRepository $deletionRequestRepository): Response
    {
        $animal = $animalRepository->findOneBySlug($slug);
        if (!$animal) {
            throw $this->createNotFoundException();
        }
        $this->denyAccessUnlessGranted('ANIMAL_VIEW', $animal);

        $professionals = [];
        foreach ($animal->getAnimalShares() as $share) {
            $user = $userRepository->findOneBy(['email' => $share->getSharedWithEmail()]);
            if ($user && $user->getAccountType() === 'PRO') {
                $professionals[] = [
                    'user' => $user,
                    'permission' => $share->getPermissionLevel(),
                    'since' => $share->getCreatedAt(),
                    'shareId' => $share->getId(),
                ];
            }
        }

        $pendingDeletionRequest = $deletionRequestRepository->findPendingForAnimal($animal->getId());

        return $this->render('animal/show.html.twig', [
            'animal' => $animal,
            'professionals' => $professionals,
            'pending_deletion_request' => $pendingDeletionRequest,
        ]);
    }

    #[Route('/{slug}/modifier', name: 'app_animal_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $slug, AnimalRepository $animalRepository, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        $animal = $animalRepository->findOneBySlug($slug);
        if (!$animal) {
            throw $this->createNotFoundException();
        }
        $this->denyAccessUnlessGranted('ANIMAL_EDIT', $animal);

        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(AnimalType::class, $animal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$animal->getBirthDate() && $form->get('age')->getData() === null) {
                $this->addFlash('danger', 'Veuillez renseigner la date de naissance ou l\'age du cheval.');
                return $this->render('animal/edit.html.twig', [
                    'animal' => $animal,
                    'form' => $form,
                ]);
            }

            $this->syncAgeAndBirthDate($form, $animal);

            if ($animal->getCoat() === 'Autre' && $form->get('coatCustom')->getData()) {
                $animal->setCoat($form->get('coatCustom')->getData());
            }

            $this->handlePhotoUpload($form, $animal);
            $this->handleUploadedDocuments($form, $animal);

            $this->handleProfessionalInvitation($request, $animal, $userRepository, $entityManager);
            $this->handleReminders($request, $animal, $user, $entityManager);

            $animal->generateSlug();
            $entityManager->flush();

            return $this->redirectToRoute('app_animal_show', [
                'slug' => $animal->getSlug(),
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('animal/edit.html.twig', [
            'animal' => $animal,
            'form' => $form,
        ]);
    }

    #[Route('/{slug}/inviter-professionnel', name: 'app_animal_invite_pro', methods: ['POST'])]
    public function inviteProfessional(Request $request, string $slug, AnimalRepository $animalRepository, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $animal = $animalRepository->findOneBySlug($slug);
        if (!$animal) {
            throw $this->createNotFoundException();
        }
        $this->denyAccessUnlessGranted('ANIMAL_EDIT', $animal);

        if (!$this->isCsrfTokenValid('invite_pro' . $animal->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('app_animal_show', ['slug' => $animal->getSlug()]);
        }

        $proId = $request->request->get('professional_id');
        if ($proId) {
            $professional = $userRepository->find((int) $proId);
            if ($professional && $professional->getAccountType() === 'PRO') {
                $existing = $entityManager->getRepository(AnimalShare::class)->findOneBy([
                    'animal' => $animal,
                    'sharedWithEmail' => $professional->getEmail(),
                ]);

                if (!$existing) {
                    $share = new AnimalShare();
                    $share->setSharedWithEmail($professional->getEmail());
                    $share->setPermissionLevel('VIEW');
                    $share->setAnimal($animal);
                    $share->setCreatedAt(new \DateTimeImmutable());
                    $entityManager->persist($share);
                    $entityManager->flush();
                }
            }
        }

        return $this->redirectToRoute('app_animal_show', ['slug' => $animal->getSlug()]);
    }

    #[Route('/{slug}/photo-rapide', name: 'app_animal_quick_photo', methods: ['POST'])]
    public function quickPhoto(Request $request, string $slug, AnimalRepository $animalRepository, EntityManagerInterface $entityManager): Response
    {
        $animal = $animalRepository->findOneBySlug($slug);
        if (!$animal) {
            throw $this->createNotFoundException();
        }
        $this->denyAccessUnlessGranted('ANIMAL_EDIT', $animal);

        if (!$this->isCsrfTokenValid('quick_photo' . $animal->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('app_animal_index');
        }

        /** @var UploadedFile|null $photoFile */
        $photoFile = $request->files->get('photo');
        if ($photoFile) {
            if ($animal->getPhoto()) {
                $oldPath = $this->photoDirectory . '/' . $animal->getPhoto();
                if (is_file($oldPath)) {
                    unlink($oldPath);
                }
            }

            if (!is_dir($this->photoDirectory)) {
                mkdir($this->photoDirectory, 0775, true);
            }

            $safeFilename = $this->slugger->slug(pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME));
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();
            $photoFile->move($this->photoDirectory, $newFilename);
            $animal->setPhoto($newFilename);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_animal_index');
    }

    #[Route('/{slug}/supprimer', name: 'app_animal_delete', methods: ['POST'])]
    public function delete(Request $request, string $slug, AnimalRepository $animalRepository, EntityManagerInterface $entityManager): Response
    {
        $animal = $animalRepository->findOneBySlug($slug);
        if (!$animal) {
            throw $this->createNotFoundException();
        }
        $this->denyAccessUnlessGranted('ANIMAL_DELETE', $animal);

        if ($this->isCsrfTokenValid('delete' . $animal->getId(), $request->getPayload()->getString('_token'))) {
            if ($animal->getPhoto()) {
                $photoPath = $this->photoDirectory . '/' . $animal->getPhoto();
                if (is_file($photoPath)) {
                    unlink($photoPath);
                }
            }

            $this->deletePhysicalDocuments($animal->getDocuments());

            $entityManager->remove($animal);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_animal_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{slug}/demander-suppression', name: 'app_animal_request_delete', methods: ['POST'])]
    public function requestDelete(
        Request $request,
        string $slug,
        AnimalRepository $animalRepository,
        AnimalDeletionRequestRepository $deletionRequestRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $animal = $animalRepository->findOneBySlug($slug);
        if (!$animal) {
            throw $this->createNotFoundException();
        }
        $this->denyAccessUnlessGranted('ANIMAL_REQUEST_DELETE', $animal);

        if (!$this->isCsrfTokenValid('request_delete' . $animal->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_animal_show', ['slug' => $animal->getSlug()]);
        }

        $existing = $deletionRequestRepository->findPendingForAnimal($animal->getId());
        if ($existing) {
            $this->addFlash('warning', 'Une demande de suppression est déjà en attente pour ce cheval.');
            return $this->redirectToRoute('app_animal_show', ['slug' => $animal->getSlug()]);
        }

        /** @var User $user */
        $user = $this->getUser();

        $deletionRequest = new AnimalDeletionRequest();
        $deletionRequest->setAnimal($animal);
        $deletionRequest->setRequestedBy($user);
        $deletionRequest->setReason($request->request->getString('reason') ?: null);

        $entityManager->persist($deletionRequest);
        $entityManager->flush();

        $this->addFlash('success', 'Votre demande de suppression a été envoyée au propriétaire de ' . $animal->getName() . '.');

        return $this->redirectToRoute('app_animal_show', ['slug' => $animal->getSlug()]);
    }

    #[Route('/demande-suppression/{id}/repondre', name: 'app_animal_deletion_respond', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function respondToDeletionRequest(
        Request $request,
        AnimalDeletionRequest $deletionRequest,
        EntityManagerInterface $entityManager,
    ): Response {
        $animal = $deletionRequest->getAnimal();

        /** @var User $user */
        $user = $this->getUser();

        if ($animal->getOwner() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if (!$deletionRequest->isPending()) {
            $this->addFlash('warning', 'Cette demande a déjà été traitée.');
            return $this->redirectToRoute('app_animal_show', ['slug' => $animal->getSlug()]);
        }

        if (!$this->isCsrfTokenValid('respond_deletion' . $deletionRequest->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_animal_show', ['slug' => $animal->getSlug()]);
        }

        $action = $request->request->getString('action');

        if ($action === 'approve') {
            $deletionRequest->setStatus(AnimalDeletionRequest::STATUS_APPROVED);
            $deletionRequest->setRespondedAt(new \DateTimeImmutable());

            if ($animal->getPhoto()) {
                $photoPath = $this->photoDirectory . '/' . $animal->getPhoto();
                if (is_file($photoPath)) {
                    unlink($photoPath);
                }
            }
            $this->deletePhysicalDocuments($animal->getDocuments());

            $entityManager->remove($animal);
            $entityManager->flush();

            $this->addFlash('success', $animal->getName() . ' a été supprimé.');
            return $this->redirectToRoute('app_animal_index');
        }

        $deletionRequest->setStatus(AnimalDeletionRequest::STATUS_REJECTED);
        $deletionRequest->setRespondedAt(new \DateTimeImmutable());
        $entityManager->flush();

        $this->addFlash('info', 'La demande de suppression a été refusée.');
        return $this->redirectToRoute('app_animal_show', ['slug' => $animal->getSlug()]);
    }

    private function syncAgeAndBirthDate(FormInterface $form, Animal $animal): void
    {
        $age = $form->get('age')->getData();

        if (!$animal->getBirthDate() && $age !== null) {
            $animal->setBirthDate(
                new \DateTimeImmutable(sprintf('%d-01-01', (int) date('Y') - (int) $age))
            );
        }
    }

    private function handlePhotoUpload(FormInterface $form, Animal $animal): void
    {
        /** @var UploadedFile|null $photoFile */
        $photoFile = $form->get('photoFile')->getData();

        if (!$photoFile) {
            return;
        }

        if ($animal->getPhoto()) {
            $oldPath = $this->photoDirectory . '/' . $animal->getPhoto();
            if (is_file($oldPath)) {
                unlink($oldPath);
            }
        }

        if (!is_dir($this->photoDirectory)) {
            mkdir($this->photoDirectory, 0775, true);
        }

        $safeFilename = $this->slugger->slug(pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME));
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

        $photoFile->move($this->photoDirectory, $newFilename);
        $animal->setPhoto($newFilename);
    }

    private function handleUploadedDocuments(FormInterface $form, Animal $animal): void
    {
        if (!$form->has('attachments')) {
            return;
        }

        $uploadedFiles = $form->get('attachments')->getData();

        if (empty($uploadedFiles)) {
            return;
        }

        $documents = $this->documentUploader->uploadMany($uploadedFiles, $this->animalUploadsDirectory);

        foreach ($documents as $document) {
            $animal->addDocument($document);
        }
    }

    private function handleProfessionalInvitation(Request $request, Animal $animal, UserRepository $userRepository, EntityManagerInterface $entityManager): void
    {
        $proId = $request->request->get('professional_id');

        if (!$proId) {
            return;
        }

        $professional = $userRepository->find((int) $proId);

        if (!$professional) {
            return;
        }

        $share = new AnimalShare();
        $share->setSharedWithEmail($professional->getEmail());
        $share->setPermissionLevel('VIEW');
        $share->setAnimal($animal);
        $share->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($share);
    }

    private function handleReminders(Request $request, Animal $animal, User $user, EntityManagerInterface $entityManager): void
    {
        $titles = $request->request->all('reminder_title');
        $dates = $request->request->all('reminder_date');
        $recurrences = $request->request->all('reminder_recurrence');

        if (empty($titles)) {
            return;
        }

        foreach ($titles as $i => $title) {
            $title = trim($title);
            $dateStr = $dates[$i] ?? '';

            if ($title === '' || $dateStr === '') {
                continue;
            }

            try {
                $scheduledAt = new \DateTimeImmutable($dateStr);
            } catch (\Exception) {
                continue;
            }

            $reminder = new Reminder();
            $reminder->setTitle($title);
            $reminder->setScheduledAt($scheduledAt);
            $reminder->setRecurrence($recurrences[$i] ?? null ?: null);
            $reminder->setAnimal($animal);
            $reminder->setOwner($user);
            $reminder->computeNextOccurrence();

            $entityManager->persist($reminder);
        }
    }

    private function deletePhysicalDocuments(array $documents): void
    {
        foreach ($documents as $document) {
            if (!isset($document['fileName'])) {
                continue;
            }

            $filePath = $this->animalUploadsDirectory . '/' . $document['fileName'];

            if (is_file($filePath)) {
                unlink($filePath);
            }
        }
    }
}
