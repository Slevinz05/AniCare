<?php

namespace App\Controller;

use App\Entity\HealthBookEntry;
use App\Entity\HealthBookEntryShare;
use App\Entity\Structure;
use App\Entity\User;
use App\Form\HealthBookEntryType;
use App\Repository\AnimalRepository;
use App\Repository\AppointmentRepository;
use App\Repository\HealthBookEntryRepository;
use App\Service\DocumentUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Entity\HealthBookEntryAuditLog;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/consultations')]
#[IsGranted('ROLE_USER')]
final class HealthBookEntryController extends AbstractController
{
    public function __construct(
        private readonly DocumentUploader $documentUploader,
        private readonly MailerInterface $mailer,
        private readonly UserPasswordHasherInterface $passwordHasher,

        #[Autowire('%kernel.project_dir%/var/uploads/health-book-entries')]
        private readonly string $healthBookEntryUploadsDirectory,

        #[Autowire('%kernel.project_dir%/var/uploads/animals')]
        private readonly string $animalUploadsDirectory,
    ) {
    }

    #[Route(name: 'app_health_book_entry_index', methods: ['GET'])]
    public function index(Request $request, HealthBookEntryRepository $healthBookEntryRepository, AnimalRepository $animalRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $isPro = $this->isGranted('ROLE_PRO');

        if (!$isPro || !$user->isInProSpace()) {
            $this->addFlash('danger', 'L\'historique des consultations est accessible uniquement depuis l\'espace professionnel.');
            return $this->redirectToRoute('app_home');
        }

        $filters = [
            'animal_id' => $request->query->get('animal'),
            'type' => $request->query->get('type'),
            'date_from' => $request->query->get('date_from'),
            'date_to' => $request->query->get('date_to'),
            'status' => $request->query->get('status'),
            'q' => $request->query->get('q'),
        ];

        $hasFilters = !empty(array_filter($filters));

        $animals = $isPro
            ? $animalRepository->findByProHistory($user)
            : $animalRepository->findAccessibleAnimals($user);

        $canCreateConsultation = $isPro && $user->isInProSpace();

        return $this->render('health_book_entry/index.html.twig', [
            'health_book_entries' => $healthBookEntryRepository->findAccessibleByUser($user, $isPro, $filters),
            'is_pro' => $isPro,
            'can_create_consultation' => $canCreateConsultation,
            'filters' => $filters,
            'has_filters' => $hasFilters,
            'animals' => $animals,
        ]);
    }

    #[Route('/importer', name: 'app_health_book_entry_import', methods: ['GET', 'POST'])]
    public function import(
        Request $request,
        EntityManagerInterface $entityManager,
        AnimalRepository $animalRepository,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $animals = $animalRepository->findAccessibleAnimals($user);

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('import_documents', $request->request->get('_token'))) {
                $this->addFlash('danger', 'Token CSRF invalide.');
                return $this->redirectToRoute('app_health_book_entry_import');
            }

            $mode = $request->request->get('mode', 'existing');
            $animal = null;

            if ($mode === 'new') {
                $newName = trim($request->request->get('new_animal_name', ''));
                if (!$newName) {
                    $this->addFlash('danger', 'Veuillez saisir le nom du cheval.');
                    return $this->redirectToRoute('app_health_book_entry_import');
                }

                $animal = new \App\Entity\Animal();
                $animal->setName($newName);
                $animal->setOwner($user);
                $animal->setSpecies('Cheval');
                $animal->setGender($request->request->get('new_animal_gender', 'Hongre'));
                $entityManager->persist($animal);
                $entityManager->flush();
                $animal->ensureSlug();
                $entityManager->flush();
            } else {
                $animalId = $request->request->get('animal_id');
                if ($animalId) {
                    $animal = $animalRepository->find((int) $animalId);
                }
            }

            if (!$animal) {
                $this->addFlash('danger', 'Veuillez sélectionner ou créer un cheval.');
                return $this->redirectToRoute('app_health_book_entry_import');
            }

            $uploadedFiles = $request->files->all('documents');
            if (empty($uploadedFiles)) {
                $this->addFlash('danger', 'Veuillez sélectionner au moins un document.');
                return $this->redirectToRoute('app_health_book_entry_import');
            }

            $documents = $this->documentUploader->uploadMany(
                $uploadedFiles,
                $this->animalUploadsDirectory,
            );

            foreach ($documents as $document) {
                $animal->addDocument($document);
            }

            $entityManager->flush();

            $this->addFlash('success', count($documents) . ' document(s) importé(s) avec succès.');

            return $this->redirect(
                $this->generateUrl('app_animal_show', ['slug' => $animal->getSlug()]) . '#panel-documents'
            );
        }

        return $this->render('health_book_entry/import.html.twig', [
            'animals' => $animals,
        ]);
    }

    #[Route('/nouvelle', name: 'app_health_book_entry_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, AnimalRepository $animalRepository, AppointmentRepository $appointmentRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->hasProSpace() || !$user->isInProSpace()) {
            $this->addFlash('danger', 'Seul un professionnel en espace PRO peut créer une consultation.');
            return $this->redirectToRoute('app_health_book_entry_index');
        }

        $healthBookEntry = new HealthBookEntry();

        $presetDateString = $request->query->get('preset_date');
        if ($presetDateString) {
            try {
                $healthBookEntry->setDate(new \DateTimeImmutable($presetDateString));
            } catch (\Exception) {
            }
        }

        $animalId = $request->query->get('animal');
        if ($animalId) {
            $animal = $animalRepository->find((int) $animalId);
            if ($animal) {
                $healthBookEntry->setAnimal($animal);
            }
        }

        $appointmentId = $request->query->get('appointment');
        if ($appointmentId) {
            $appointment = $appointmentRepository->find((int) $appointmentId);
            if ($appointment && $appointment->getCreatedBy() === $user) {
                $healthBookEntry->setAppointment($appointment);
                $healthBookEntry->setDate($appointment->getScheduledAt());
                if (!$healthBookEntry->getAnimal() && $appointment->getAnimals()->count() > 0) {
                    $healthBookEntry->setAnimal($appointment->getAnimals()->first());
                }
                if ($appointment->getConsultationType()) {
                    $healthBookEntry->setType($appointment->getConsultationType());
                }
                if ($appointment->getReason()) {
                    $healthBookEntry->setTitle($appointment->getReason());
                }
            }
        }

        if ($this->isGranted('ROLE_PRO')) {
            $healthBookEntry->setVeterinarian($user);

            $defaultNotes = $user->getDefaultPublicNotes();
            if ($defaultNotes) {
                $healthBookEntry->setDescription($defaultNotes);
            }
        }

        $rehabTemplates = [];
        foreach ($user->getRehabilitationTemplates() as $tpl) {
            $name = $tpl['name'] ?? '';
            if ($name !== '') {
                $rehabTemplates[$name] = $name;
            }
        }

        $form = $this->createForm(HealthBookEntryType::class, $healthBookEntry, [
            'user' => $user,
            'rehabilitation_templates' => $rehabTemplates,
            'preset_appointment_id' => $healthBookEntry->getAppointment()?->getId() ?? 0,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->syncTypeCustom($form, $healthBookEntry);
            $this->handleUploadedDocuments($form, $healthBookEntry);

            $isDraft = $request->request->get('save_draft') !== null;
            if ($isDraft) {
                $healthBookEntry->initAsDraft();
            } else {
                $healthBookEntry->initAsPublished();
            }
            $healthBookEntry->setCreatedBy($user);

            $behaviorScore = $request->request->get('behavior_score');
            $healthBookEntry->setBehaviorScore($behaviorScore !== null && $behaviorScore !== '' ? (int) $behaviorScore : null);
            $bodyConditionScore = $request->request->get('body_condition_score');
            $healthBookEntry->setBodyConditionScore($bodyConditionScore !== null && $bodyConditionScore !== '' ? (int) $bodyConditionScore : null);
            $workDone = $request->request->get('work_done');
            $healthBookEntry->setWorkDone($workDone !== null && $workDone !== '' ? (int) $workDone : null);

            $newAnimalCreated = false;
            $referentUser = null;
            $referentIsNew = false;
            $newAnimalName = $request->request->get('new_animal_name');

            // Resolve referent: existing user or new contact
            $referentId = $request->request->get('consultation_referent_id');
            if ($referentId) {
                $referentUser = $entityManager->getRepository(User::class)->find((int) $referentId);
            }

            $refEmail = trim($request->request->get('referent_contact_email', ''));
            $refFirstName = trim($request->request->get('referent_contact_firstname', ''));
            $refLastName = trim($request->request->get('referent_contact_lastname', ''));

            if (!$referentUser && $refEmail && $refFirstName && $refLastName) {
                $existingRef = $entityManager->getRepository(User::class)->findOneBy(['email' => $refEmail]);
                if ($existingRef) {
                    $referentUser = $existingRef;
                } else {
                    $referentUser = new User();
                    $referentUser->setFirstName($refFirstName);
                    $referentUser->setLastName($refLastName);
                    $referentUser->setEmail($refEmail);
                    $refPhone = trim($request->request->get('referent_contact_phone', ''));
                    if ($refPhone) {
                        $referentUser->setPhone($refPhone);
                    }
                    $referentUser->setPassword($this->passwordHasher->hashPassword($referentUser, bin2hex(random_bytes(8))));
                    $referentUser->setRoles(['ROLE_USER']);
                    $referentUser->setAccountType('Propriétaire');
                    $referentUser->setActivationToken(bin2hex(random_bytes(32)));
                    $entityManager->persist($referentUser);
                    $referentIsNew = true;
                }
            }

            if ($newAnimalName && !$healthBookEntry->getAnimal()) {
                $animal = new \App\Entity\Animal();
                $animal->setName($newAnimalName);
                $animal->setGender($request->request->get('new_animal_gender', 'Mâle'));
                $animal->setCreatedByPro($user);
                $animal->setOwner($referentUser ?? $user);

                if ($referentUser) {
                    $referent = new \App\Entity\AnimalReferent();
                    $referent->setAnimal($animal);
                    $referent->setUser($referentUser);
                    $referent->setRole('principal');
                    $referent->setStatus('active');
                    $entityManager->persist($referent);
                }

                if ($breed = $request->request->get('new_animal_breed')) {
                    $animal->setBreed($breed);
                }
                if ($birthdate = $request->request->get('new_animal_birthdate')) {
                    try { $animal->setBirthDate(new \DateTimeImmutable($birthdate)); } catch (\Exception) {}
                }
                if ($coat = $request->request->get('new_animal_coat')) {
                    $animal->setCoat($coat);
                }
                if ($identification = $request->request->get('new_animal_identification')) {
                    $animal->setIdentificationNumber($identification);
                }
                $entityManager->persist($animal);
                $healthBookEntry->setAnimal($animal);
                $newAnimalCreated = true;
            }

            // If no referent was explicitly selected but the animal has one, use it for notification
            if (!$referentUser && $healthBookEntry->getAnimal()) {
                $animalReferents = $entityManager->getRepository(\App\Entity\AnimalReferent::class)->findBy([
                    'animal' => $healthBookEntry->getAnimal(),
                    'role' => 'principal',
                    'status' => 'active',
                ]);
                if (!empty($animalReferents)) {
                    $referentUser = $animalReferents[0]->getUser();
                }
            }

            if (!$healthBookEntry->getTitle()) {
                $type = $healthBookEntry->getType() ?? 'Consultation';
                $date = $healthBookEntry->getDate()?->format('d/m/Y') ?? date('d/m/Y');
                $healthBookEntry->setTitle($type . ' du ' . $date);
            }

            $entityManager->persist($healthBookEntry);

            $auditLog = new HealthBookEntryAuditLog(
                $healthBookEntry,
                $isDraft ? HealthBookEntryAuditLog::ACTION_CREATED_DRAFT : HealthBookEntryAuditLog::ACTION_CREATED_PUBLISHED,
                $user,
                ['type' => $healthBookEntry->getType(), 'animal' => $healthBookEntry->getAnimal()?->getName()]
            );
            $entityManager->persist($auditLog);
            $entityManager->flush();

            if ($newAnimalCreated) {
                $healthBookEntry->getAnimal()->generateCompleteToken();
                $healthBookEntry->getAnimal()->ensureSlug();
                $entityManager->flush();
            }

            // ── Handle sharing (multi-destinataires) ──
            $sharesJson = $request->request->get('shares_data');
            if ($sharesJson) {
                $sharesData = json_decode($sharesJson, true) ?? [];
                if (!empty($sharesData)) {
                    $healthBookEntry->setSharedAt(new \DateTimeImmutable());
                    $this->handleMultiSharing($healthBookEntry, $sharesData, $entityManager, $user);
                    $entityManager->flush();
                }
            }

            // ── Notify referent (skip if referent is the PRO creating the consultation) ──
            if (!$isDraft && $referentUser && $referentUser !== $user) {
                $animalName = $healthBookEntry->getAnimal()?->getName() ?? '';

                if ($referentIsNew) {
                    $activationUrl = $referentUser->getActivationToken()
                        ? $this->generateUrl('app_activation', ['token' => $referentUser->getActivationToken()], UrlGeneratorInterface::ABSOLUTE_URL)
                        : $this->generateUrl('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL);
                    $this->sendShareEmail(
                        $referentUser->getEmail(),
                        'referent_invitation',
                        [
                            'referent_name' => $referentUser->getFullName(),
                            'sender_name' => $user->getFullName(),
                            'animal_name' => $animalName,
                            'app_url' => $this->generateUrl('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL),
                            'activation_url' => $activationUrl,
                            'referent_email' => $referentUser->getEmail(),
                            'is_new_user' => true,
                        ]
                    );
                    $this->addFlash('success', 'Invitation envoyée à ' . $referentUser->getFullName() . ' (nouveau référent).');
                } else {
                    $entryUrl = $this->generateUrl('app_health_book_entry_show', ['id' => $healthBookEntry->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
                    $this->sendShareEmail(
                        $referentUser->getEmail(),
                        'referent_notification',
                        [
                            'referent_name' => $referentUser->getFullName(),
                            'sender_name' => $user->getFullName(),
                            'animal_name' => $animalName,
                            'entry_type' => $healthBookEntry->getType() ?? 'Consultation',
                            'entry_date' => $healthBookEntry->getDate()?->format('d/m/Y') ?? '',
                            'app_url' => $entryUrl,
                        ]
                    );
                    $this->addFlash('info', 'Notification envoyée au référent ' . $referentUser->getFullName() . '.');
                }
            }

            if ($isDraft) {
                $this->addFlash('success', 'Brouillon enregistré.');
                return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
            }

            $params = ['id' => $healthBookEntry->getId()];
            if ($newAnimalCreated) {
                $animal = $healthBookEntry->getAnimal();
                $params['complete_animal'] = $animal->getId();
                if ($animal->getCompleteToken()) {
                    $params['complete_token'] = $animal->getCompleteToken();
                }
            }

            return $this->redirectToRoute('app_health_book_entry_show', $params, Response::HTTP_SEE_OTHER);
        }

        return $this->render('health_book_entry/new.html.twig', [
            'health_book_entry' => $healthBookEntry,
            'form' => $form,
        ]);
    }

    #[Route('/api/animals', name: 'app_consultation_animals_api', methods: ['GET'])]
    #[IsGranted('ROLE_PRO')]
    public function animalsApi(Request $request, AnimalRepository $animalRepository, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $pro */
        $pro = $this->getUser();
        $query = trim($request->query->get('q', ''));
        $referentId = $request->query->getInt('referent_id');

        $groups = [];

        if ($referentId) {
            $referent = $em->getRepository(User::class)->find($referentId);
            if ($referent) {
                $referentAnimals = $animalRepository->findByOwner($referent);
                if ($query) {
                    $referentAnimals = array_filter($referentAnimals, fn($a) =>
                        stripos($a->getName(), $query) !== false
                    );
                }
                $groups['referent'] = [
                    'label' => 'Animaux de ' . $referent->getFullName(),
                    'animals' => array_values(array_map(fn($a) => $this->serializeAnimal($a), $referentAnimals)),
                ];
            }
        }

        $proAnimals = $animalRepository->findByProHistory($pro, $query ?: null);
        $referentAnimalIds = array_map(fn($a) => $a['id'], $groups['referent']['animals'] ?? []);
        $proAnimals = array_filter($proAnimals, fn($a) => !in_array($a->getId(), $referentAnimalIds));

        if (!empty($proAnimals)) {
            $groups['pro'] = [
                'label' => 'Mes patients récents',
                'animals' => array_values(array_map(fn($a) => $this->serializeAnimal($a), $proAnimals)),
            ];
        }

        return $this->json($groups);
    }

    private function serializeAnimal(\App\Entity\Animal $animal): array
    {
        $owner = $animal->getOwner();
        $referentName = '';
        foreach ($animal->getReferents() as $ref) {
            if ($ref->isPrincipal() && $ref->isActive()) {
                $referentName = $ref->getUser()->getFullName();
                break;
            }
        }
        if (!$referentName && $owner) {
            $referentName = $owner->getFullName();
        }

        return [
            'id' => $animal->getId(),
            'name' => $animal->getName(),
            'breed' => $animal->getBreed() ?? '',
            'coat' => $animal->getCoat() ?? '',
            'gender' => $animal->getGender() ?? '',
            'referent' => $referentName,
            'identification' => $animal->getIdentificationNumber() ?? '',
        ];
    }

    #[Route('/{id}/details', name: 'app_health_book_entry_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(HealthBookEntry $healthBookEntry): Response
    {
        $this->denyAccessUnlessGranted('ANIMAL_VIEW', $healthBookEntry->getAnimal());

        /** @var User $user */
        $user = $this->getUser();

        if ($healthBookEntry->isDraft() && !$healthBookEntry->isAuthor($user)) {
            throw $this->createAccessDeniedException('Les brouillons ne sont visibles que par leur auteur.');
        }

        $isAuthor = $healthBookEntry->isAuthor($user);
        $viewerShareMode = $isAuthor ? null : $healthBookEntry->getShareModeFor($user);
        $canShare = $healthBookEntry->canBeSharedBy($user);

        return $this->render('health_book_entry/show.html.twig', [
            'health_book_entry' => $healthBookEntry,
            'is_author' => $isAuthor,
            'can_share' => $canShare,
            'viewer_share_mode' => $viewerShareMode,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_health_book_entry_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, HealthBookEntry $healthBookEntry, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$healthBookEntry->isAuthor($user)) {
            throw $this->createAccessDeniedException('Seul l\'auteur peut modifier cette consultation.');
        }

        if ($healthBookEntry->isArchived()) {
            $this->addFlash('danger', 'Une consultation archivée ne peut plus être modifiée.');
            return $this->redirectToRoute('app_health_book_entry_show', ['id' => $healthBookEntry->getId()]);
        }

        $rehabTemplates = [];
        foreach ($user->getRehabilitationTemplates() as $tpl) {
            $name = $tpl['name'] ?? '';
            if ($name !== '') {
                $rehabTemplates[$name] = $name;
            }
        }

        $form = $this->createForm(HealthBookEntryType::class, $healthBookEntry, [
            'user' => $user,
            'rehabilitation_templates' => $rehabTemplates,
            'preset_appointment_id' => $healthBookEntry->getAppointment()?->getId() ?? 0,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->syncTypeCustom($form, $healthBookEntry);
            $this->handleUploadedDocuments($form, $healthBookEntry);

            $isDraft = $request->request->get('save_draft') !== null;
            $wasPublished = !$healthBookEntry->isDraft();

            if ($wasPublished && $isDraft) {
                $this->addFlash('danger', 'Une consultation publiée ne peut pas repasser en brouillon. Utilisez l\'archivage si nécessaire.');
                return $this->redirectToRoute('app_health_book_entry_show', ['id' => $healthBookEntry->getId()]);
            }

            $behaviorScore = $request->request->get('behavior_score');
            $healthBookEntry->setBehaviorScore($behaviorScore !== null && $behaviorScore !== '' ? (int) $behaviorScore : null);
            $bodyConditionScore = $request->request->get('body_condition_score');
            $healthBookEntry->setBodyConditionScore($bodyConditionScore !== null && $bodyConditionScore !== '' ? (int) $bodyConditionScore : null);
            $workDone = $request->request->get('work_done');
            $healthBookEntry->setWorkDone($workDone !== null && $workDone !== '' ? (int) $workDone : null);

            if ($wasPublished) {
                $correctionReason = trim($request->request->get('correction_reason', ''));
                $healthBookEntry->applyCorrection($user, $correctionReason ?: null);

                $entityManager->persist(new HealthBookEntryAuditLog(
                    $healthBookEntry,
                    HealthBookEntryAuditLog::ACTION_CORRECTED,
                    $user,
                    ['version' => $healthBookEntry->getVersion(), 'reason' => $correctionReason ?: null]
                ));
            } else {
                if (!$isDraft) {
                    $healthBookEntry->initAsPublished();
                    $entityManager->persist(new HealthBookEntryAuditLog(
                        $healthBookEntry,
                        HealthBookEntryAuditLog::ACTION_PUBLISHED,
                        $user
                    ));
                } else {
                    $healthBookEntry->setUpdatedAt(new \DateTimeImmutable());
                }
            }

            $entityManager->flush();

            if ($isDraft) {
                $this->addFlash('success', 'Brouillon enregistré.');
                return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
            }

            if ($wasPublished) {
                $this->addFlash('success', 'Correction enregistrée (version ' . $healthBookEntry->getVersion() . ').');
            }

            return $this->redirectToRoute('app_health_book_entry_show', [
                'id' => $healthBookEntry->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('health_book_entry/edit.html.twig', [
            'health_book_entry' => $healthBookEntry,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/valider', name: 'app_health_book_entry_validate', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function validate(Request $request, HealthBookEntry $healthBookEntry, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ANIMAL_EDIT_HEALTH', $healthBookEntry->getAnimal());

        /** @var User $user */
        $user = $this->getUser();

        if (!$healthBookEntry->isAuthor($user)) {
            throw $this->createAccessDeniedException('Seul l\'auteur peut valider cette consultation.');
        }

        if (!$healthBookEntry->isDraft()) {
            $this->addFlash('warning', 'Cette consultation est déjà validée.');
            return $this->redirectToRoute('app_health_book_entry_show', ['id' => $healthBookEntry->getId()]);
        }

        if ($this->isCsrfTokenValid('validate' . $healthBookEntry->getId(), $request->request->get('_token'))) {
            $healthBookEntry->publish();

            $entityManager->persist(new HealthBookEntryAuditLog(
                $healthBookEntry,
                HealthBookEntryAuditLog::ACTION_PUBLISHED,
                $user,
            ));
            $entityManager->flush();

            $this->addFlash('success', 'Consultation validée avec succès.');
        }

        return $this->redirectToRoute('app_health_book_entry_show', ['id' => $healthBookEntry->getId()]);
    }

    #[Route('/{id}/partager', name: 'app_health_book_entry_share', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function share(Request $request, HealthBookEntry $healthBookEntry, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$healthBookEntry->canBeSharedBy($user)) {
            throw $this->createAccessDeniedException('Vous n\'avez pas le droit de partager cette consultation.');
        }

        if ($healthBookEntry->isDraft()) {
            $this->addFlash('warning', 'Veuillez d\'abord valider le brouillon avant de le partager.');
            return $this->redirectToRoute('app_health_book_entry_show', ['id' => $healthBookEntry->getId()]);
        }

        if ($healthBookEntry->isArchived()) {
            $this->addFlash('danger', 'Une consultation archivée ne peut plus être partagée.');
            return $this->redirectToRoute('app_health_book_entry_show', ['id' => $healthBookEntry->getId()]);
        }

        if ($this->isCsrfTokenValid('share' . $healthBookEntry->getId(), $request->request->get('_token'))) {
            $sharesJson = $request->request->get('shares_data', '');
            $sharesData = $sharesJson ? json_decode($sharesJson, true) : [];

            if (!empty($sharesData)) {
                $shareCountBefore = $healthBookEntry->getShares()->count();
                $this->handleMultiSharing($healthBookEntry, $sharesData, $entityManager, $user);
                $healthBookEntry->setSharedAt(new \DateTimeImmutable());
                $healthBookEntry->setUpdatedAt(new \DateTimeImmutable());

                $entityManager->persist(new HealthBookEntryAuditLog(
                    $healthBookEntry,
                    HealthBookEntryAuditLog::ACTION_SHARED,
                    $user,
                    ['new_shares' => $healthBookEntry->getShares()->count() - $shareCountBefore]
                ));
                $entityManager->flush();
            } else {
                $this->addFlash('warning', 'Aucun destinataire sélectionné.');
            }
        }

        return $this->redirectToRoute('app_health_book_entry_show', ['id' => $healthBookEntry->getId()]);
    }

    #[Route('/{id}/document/{fileName}/supprimer', name: 'app_health_book_entry_document_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteDocument(Request $request, HealthBookEntry $healthBookEntry, string $fileName, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ANIMAL_EDIT_HEALTH', $healthBookEntry->getAnimal());

        if ($this->isCsrfTokenValid('delete_doc' . $fileName, $request->request->get('_token'))) {
            $filePath = $this->healthBookEntryUploadsDirectory . '/' . $fileName;
            if (is_file($filePath)) {
                unlink($filePath);
            }

            $healthBookEntry->removeDocument($fileName);
            $entityManager->flush();

            $this->addFlash('success', 'Document supprime.');
        }

        return $this->redirectToRoute('app_health_book_entry_show', ['id' => $healthBookEntry->getId()]);
    }

    #[Route('/{id}/supprimer', name: 'app_health_book_entry_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, HealthBookEntry $healthBookEntry, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ANIMAL_DELETE', $healthBookEntry->getAnimal());

        /** @var User $user */
        $user = $this->getUser();

        if (!$healthBookEntry->canBeDeleted()) {
            $this->addFlash('danger', 'Une consultation publiée ne peut pas être supprimée. Utilisez l\'archivage.');
            return $this->redirectToRoute('app_health_book_entry_show', ['id' => $healthBookEntry->getId()]);
        }

        if ($this->isCsrfTokenValid('delete' . $healthBookEntry->getId(), $request->getPayload()->getString('_token'))) {
            $this->deletePhysicalDocuments($healthBookEntry->getDocuments());

            $entityManager->persist(new HealthBookEntryAuditLog(
                $healthBookEntry,
                HealthBookEntryAuditLog::ACTION_DELETED,
                $user,
                ['title' => $healthBookEntry->getTitle()]
            ));

            $entityManager->remove($healthBookEntry);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_health_book_entry_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/archiver', name: 'app_health_book_entry_archive', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function archive(Request $request, HealthBookEntry $healthBookEntry, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$healthBookEntry->isAuthor($user)) {
            throw $this->createAccessDeniedException('Seul l\'auteur peut archiver cette consultation.');
        }

        if (!$healthBookEntry->isPublished()) {
            $this->addFlash('danger', 'Seule une consultation publiée peut être archivée.');
            return $this->redirectToRoute('app_health_book_entry_show', ['id' => $healthBookEntry->getId()]);
        }

        if ($this->isCsrfTokenValid('archive' . $healthBookEntry->getId(), $request->request->get('_token'))) {
            $reason = trim($request->request->get('archive_reason', ''));
            $healthBookEntry->archive($user, $reason ?: null);

            $entityManager->persist(new HealthBookEntryAuditLog(
                $healthBookEntry,
                HealthBookEntryAuditLog::ACTION_ARCHIVED,
                $user,
                ['reason' => $reason ?: null, 'had_shares' => $healthBookEntry->getShares()->count()]
            ));
            $entityManager->flush();

            $this->addFlash('success', 'Consultation archivée.');
        }

        return $this->redirectToRoute('app_health_book_entry_index');
    }

    private function syncTypeCustom(FormInterface $form, HealthBookEntry $healthBookEntry): void
    {
        if ($healthBookEntry->getType() === 'Autre') {
            $custom = $form->get('typeCustom')->getData();
            if ($custom) {
                $healthBookEntry->setType($custom);
            }
        }
    }

    private function handleUploadedDocuments(FormInterface $form, HealthBookEntry $healthBookEntry): void
    {
        if (!$form->has('attachments')) {
            return;
        }

        $uploadedFiles = $form->get('attachments')->getData();

        if (empty($uploadedFiles)) {
            return;
        }

        $documents = $this->documentUploader->uploadMany($uploadedFiles, $this->healthBookEntryUploadsDirectory);

        foreach ($documents as $document) {
            $healthBookEntry->addDocument($document);
        }
    }

    private function deletePhysicalDocuments(array $documents): void
    {
        foreach ($documents as $document) {
            if (!isset($document['fileName'])) {
                continue;
            }

            $filePath = $this->healthBookEntryUploadsDirectory . '/' . $document['fileName'];

            if (is_file($filePath)) {
                unlink($filePath);
            }
        }
    }

    private function handleMultiSharing(
        HealthBookEntry $entry,
        array $sharesData,
        EntityManagerInterface $em,
        User $sender,
    ): void {
        $appUrl = $this->generateUrl('app_health_book_entry_show', ['id' => $entry->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        $hasShared = false;

        foreach ($sharesData as $shareData) {
            $type = $shareData['type'] ?? '';
            $mode = $shareData['mode'] ?? 'readonly';
            if (!in_array($mode, ['readonly', 'summary'], true)) {
                $mode = 'readonly';
            }

            $share = new HealthBookEntryShare();
            $share->setMode($mode);
            $entry->addShare($share);

            $emailParams = [
                'sender_name' => $sender->getFullName(),
                'animal_name' => $entry->getAnimal()?->getName() ?? '',
                'entry_type' => $entry->getType() ?? 'Consultation',
                'share_mode' => $mode,
                'app_url' => $appUrl,
            ];

            switch ($type) {
                case 'user':
                    $userId = (int) ($shareData['user_id'] ?? 0);
                    $targetUser = $em->getRepository(User::class)->find($userId);
                    if ($targetUser) {
                        $share->setSharedWithUser($targetUser);
                        $share->setSharedWithEmail($targetUser->getEmail());
                        $hasShared = true;
                        $this->sendShareEmail(
                            $targetUser->getEmail(),
                            'consultation_shared',
                            array_merge($emailParams, ['recipient_email' => $targetUser->getEmail(), 'is_new_user' => false, 'activation_url' => null])
                        );
                        $this->addFlash('success', 'Partagé avec ' . $targetUser->getFullName() . ' (' . $share->getModeLabel() . ').');
                    }
                    break;

                case 'new_user':
                    $email = $shareData['email'] ?? '';
                    $firstName = $shareData['first_name'] ?? '';
                    $lastName = $shareData['last_name'] ?? '';
                    if ($email && $firstName && $lastName) {
                        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
                        if ($existingUser) {
                            $share->setSharedWithUser($existingUser);
                            $share->setSharedWithEmail($email);
                        } else {
                            $newUser = new User();
                            $newUser->setFirstName($firstName);
                            $newUser->setLastName($lastName);
                            $newUser->setEmail($email);
                            $newUser->setPassword($this->passwordHasher->hashPassword($newUser, bin2hex(random_bytes(8))));
                            $newUser->setRoles(['ROLE_USER']);
                            $newUser->setAccountType('Propriétaire');
                            $newUser->setActivationToken(bin2hex(random_bytes(32)));
                            $em->persist($newUser);
                            $em->flush();
                            $share->setSharedWithUser($newUser);
                            $share->setSharedWithEmail($email);
                        }
                        $isNew = !isset($existingUser);
                        $activationUrl = $isNew && $newUser->getActivationToken()
                            ? $this->generateUrl('app_activation', ['token' => $newUser->getActivationToken()], UrlGeneratorInterface::ABSOLUTE_URL)
                            : null;
                        $hasShared = true;
                        $this->sendShareEmail(
                            $email,
                            'consultation_shared',
                            array_merge($emailParams, ['recipient_email' => $email, 'is_new_user' => $isNew, 'activation_url' => $activationUrl])
                        );
                        $this->addFlash('success', 'Partagé avec ' . $firstName . ' ' . $lastName . ' (' . $share->getModeLabel() . ').');
                    }
                    break;

                case 'structure':
                    $structureId = (int) ($shareData['structure_id'] ?? 0);
                    $structure = $em->getRepository(Structure::class)->find($structureId);
                    if ($structure) {
                        $share->setSharedWithStructure($structure);
                        $hasShared = true;
                        $structureEmail = $structure->getEmail();
                        if ($structureEmail) {
                            $this->sendShareEmail(
                                $structureEmail,
                                'consultation_shared',
                                array_merge($emailParams, ['recipient_email' => $structureEmail, 'is_new_user' => false, 'activation_url' => null])
                            );
                        }
                        $this->addFlash('success', 'Partagé avec ' . $structure->getName() . ' (' . $share->getModeLabel() . ').');
                    }
                    break;

                case 'new_structure':
                    $structureName = $shareData['structure_name'] ?? '';
                    $structureEmail = $shareData['structure_email'] ?? '';
                    if ($structureName && $structureEmail) {
                        $structure = new Structure();
                        $structure->setName($structureName);
                        $structure->setEmail($structureEmail);
                        $structure->setCreatedBy($sender);
                        $em->persist($structure);
                        $em->flush();
                        $share->setSharedWithStructure($structure);
                        $hasShared = true;

                        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $structureEmail]);
                        $structureActivationUrl = null;
                        $isNewAccount = false;
                        if (!$existingUser) {
                            $newUser = new User();
                            $newUser->setEmail($structureEmail);
                            $newUser->setFirstName($structureName);
                            $newUser->setLastName('');
                            $newUser->setPassword($this->passwordHasher->hashPassword($newUser, bin2hex(random_bytes(8))));
                            $newUser->setRoles(['ROLE_STRUCTURE']);
                            $newUser->setAccountType('STRUCTURE');
                            $newUser->setActivationToken(bin2hex(random_bytes(32)));
                            $em->persist($newUser);
                            $em->flush();
                            $structureActivationUrl = $this->generateUrl('app_activation', ['token' => $newUser->getActivationToken()], UrlGeneratorInterface::ABSOLUTE_URL);
                            $isNewAccount = true;
                        }

                        $this->sendShareEmail(
                            $structureEmail,
                            'structure_invitation',
                            [
                                'sender_name' => $sender->getFullName(),
                                'structure_name' => $structureName,
                                'register_url' => $this->generateUrl('app_register', [], UrlGeneratorInterface::ABSOLUTE_URL),
                                'activation_url' => $structureActivationUrl,
                                'is_new_user' => $isNewAccount,
                                'claim_code' => $structure->getClaimCode(),
                            ]
                        );
                        $this->addFlash('success', 'Structure "' . $structureName . '" créée (' . $share->getModeLabel() . ').');
                    }
                    break;
            }
        }

        // Le statut ne change plus lors du partage (séparation statut/partage)
        // isShared() est désormais calculé depuis la collection shares
    }

    private function sendShareEmail(string $to, string $template, array $params): void
    {
        try {
            $email = (new Email())
                ->from('noreply@anicare.fr')
                ->to($to)
                ->subject('AniCare — Partage de consultation')
                ->html($this->renderView('emails/' . $template . '.html.twig', $params));
            $this->mailer->send($email);
        } catch (\Exception) {
        }
    }
}
