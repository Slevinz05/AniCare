<?php

namespace App\Controller;

use App\Entity\HealthBookEntry;
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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
    public function index(HealthBookEntryRepository $healthBookEntryRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $isPro = $this->isGranted('ROLE_PRO');

        return $this->render('health_book_entry/index.html.twig', [
            'health_book_entries' => $healthBookEntryRepository->findAccessibleByUser($user, $isPro),
            'is_pro' => $isPro,
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
            $healthBookEntry->setStatus($isDraft ? 'draft' : 'published');
            $healthBookEntry->setUpdatedAt(new \DateTimeImmutable());
            $healthBookEntry->setCreatedBy($user);

            $newAnimalCreated = false;
            $newAnimalName = $request->request->get('new_animal_name');
            if ($newAnimalName && !$healthBookEntry->getAnimal()) {
                $animal = new \App\Entity\Animal();
                $animal->setName($newAnimalName);
                $animal->setGender($request->request->get('new_animal_gender', 'Mâle'));
                $animal->setCreatedByPro($user);

                // Assign owner: the selected referent if available, otherwise the PRO
                $referentId = $request->request->get('consultation_referent_id');
                $referentUser = $referentId ? $entityManager->getRepository(User::class)->find((int) $referentId) : null;
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

            if (!$healthBookEntry->getTitle()) {
                $type = $healthBookEntry->getType() ?? 'Consultation';
                $date = $healthBookEntry->getDate()?->format('d/m/Y') ?? date('d/m/Y');
                $healthBookEntry->setTitle($type . ' du ' . $date);
            }

            $entityManager->persist($healthBookEntry);
            $entityManager->flush();

            if ($newAnimalCreated) {
                $healthBookEntry->getAnimal()->ensureSlug();
                $entityManager->flush();
            }

            // ── Handle sharing ──
            $shareTargetType = $request->request->get('share_target_type');
            $shareMode = $request->request->get('share_mode');
            if ($shareTargetType && $shareMode) {
                $healthBookEntry->setShareMode($shareMode);
                $healthBookEntry->setSharedAt(new \DateTimeImmutable());

                $this->handleSharing($healthBookEntry, $request, $entityManager, $user);
                $entityManager->flush();
            }

            if ($isDraft) {
                $this->addFlash('success', 'Brouillon enregistré.');
                return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
            }

            $params = ['id' => $healthBookEntry->getId()];
            if ($newAnimalCreated) {
                $params['complete_animal'] = $healthBookEntry->getAnimal()->getId();
            }

            return $this->redirectToRoute('app_health_book_entry_show', $params, Response::HTTP_SEE_OTHER);
        }

        return $this->render('health_book_entry/new.html.twig', [
            'health_book_entry' => $healthBookEntry,
            'form' => $form,
        ]);
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

        return $this->render('health_book_entry/show.html.twig', [
            'health_book_entry' => $healthBookEntry,
            'is_author' => $healthBookEntry->isAuthor($user),
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_health_book_entry_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, HealthBookEntry $healthBookEntry, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ANIMAL_EDIT_HEALTH', $healthBookEntry->getAnimal());

        /** @var User $user */
        $user = $this->getUser();

        if (!$healthBookEntry->isAuthor($user)) {
            if ($healthBookEntry->isDraft()) {
                throw $this->createAccessDeniedException('Seul l\'auteur peut modifier un brouillon.');
            }
            if ($healthBookEntry->getStatus() === 'published') {
                throw $this->createAccessDeniedException('Seul l\'auteur peut modifier un compte-rendu publié.');
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
            $healthBookEntry->setStatus($isDraft ? 'draft' : 'published');

            $entityManager->flush();

            if ($isDraft) {
                $this->addFlash('success', 'Brouillon enregistré.');
                return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
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
            $healthBookEntry->setStatus('published');
            $healthBookEntry->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'Consultation validée avec succès.');
        }

        return $this->redirectToRoute('app_health_book_entry_show', ['id' => $healthBookEntry->getId()]);
    }

    #[Route('/{id}/partager', name: 'app_health_book_entry_share', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function share(Request $request, HealthBookEntry $healthBookEntry, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ANIMAL_EDIT_HEALTH', $healthBookEntry->getAnimal());

        /** @var User $user */
        $user = $this->getUser();

        if (!$healthBookEntry->isAuthor($user)) {
            throw $this->createAccessDeniedException('Seul l\'auteur peut partager cette consultation.');
        }

        if ($healthBookEntry->isDraft()) {
            $this->addFlash('warning', 'Veuillez d\'abord valider le brouillon avant de le partager.');
            return $this->redirectToRoute('app_health_book_entry_show', ['id' => $healthBookEntry->getId()]);
        }

        if ($this->isCsrfTokenValid('share' . $healthBookEntry->getId(), $request->request->get('_token'))) {
            $healthBookEntry->setStatus('shared');
            $healthBookEntry->setSharedAt(new \DateTimeImmutable());
            $healthBookEntry->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'Compte-rendu transmis au référent.');
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

        if ($this->isCsrfTokenValid('delete' . $healthBookEntry->getId(), $request->getPayload()->getString('_token'))) {
            $this->deletePhysicalDocuments($healthBookEntry->getDocuments());

            $entityManager->remove($healthBookEntry);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_health_book_entry_index', [], Response::HTTP_SEE_OTHER);
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

    private function handleSharing(
        HealthBookEntry $entry,
        Request $request,
        EntityManagerInterface $em,
        User $sender,
    ): void {
        $type = $request->request->get('share_target_type');
        $appUrl = $this->generateUrl('app_health_book_entry_show', ['id' => $entry->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        $emailParams = [
            'sender_name' => $sender->getFullName(),
            'animal_name' => $entry->getAnimal()?->getName() ?? '',
            'entry_type' => $entry->getType() ?? 'Consultation',
            'share_mode' => $entry->getShareMode(),
            'app_url' => $appUrl,
        ];

        switch ($type) {
            case 'user':
                $userId = (int) $request->request->get('share_with_user_id');
                $targetUser = $em->getRepository(User::class)->find($userId);
                if ($targetUser) {
                    $entry->setSharedWithUser($targetUser);
                    $entry->setSharedWithEmail($targetUser->getEmail());
                    $entry->setStatus('shared');
                    $this->sendShareEmail(
                        $targetUser->getEmail(),
                        'consultation_shared',
                        array_merge($emailParams, [
                            'recipient_email' => $targetUser->getEmail(),
                            'is_new_user' => false,
                        ])
                    );
                    $this->addFlash('success', 'Consultation partagée avec ' . $targetUser->getFullName() . '.');
                }
                break;

            case 'new_user':
                $email = $request->request->get('share_with_email');
                $firstName = $request->request->get('new_user_first_name');
                $lastName = $request->request->get('new_user_last_name');
                if ($email && $firstName && $lastName) {
                    $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
                    if ($existingUser) {
                        $entry->setSharedWithUser($existingUser);
                        $entry->setSharedWithEmail($email);
                        $entry->setStatus('shared');
                        $this->sendShareEmail(
                            $email,
                            'consultation_shared',
                            array_merge($emailParams, [
                                'recipient_email' => $email,
                                'is_new_user' => false,
                            ])
                        );
                        $this->addFlash('info', 'L\'utilisateur existe déjà — consultation partagée avec ' . $existingUser->getFullName() . '.');
                    } else {
                        $newUser = new User();
                        $newUser->setFirstName($firstName);
                        $newUser->setLastName($lastName);
                        $newUser->setEmail($email);
                        $tempPassword = bin2hex(random_bytes(8));
                        $newUser->setPassword($this->passwordHasher->hashPassword($newUser, $tempPassword));
                        $newUser->setRoles(['ROLE_USER']);
                        $newUser->setAccountType('Propriétaire');
                        $em->persist($newUser);
                        $em->flush();

                        $entry->setSharedWithUser($newUser);
                        $entry->setSharedWithEmail($email);
                        $entry->setStatus('shared');

                        $registerUrl = $this->generateUrl('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL);
                        $this->sendShareEmail(
                            $email,
                            'consultation_shared',
                            array_merge($emailParams, [
                                'recipient_email' => $email,
                                'is_new_user' => true,
                            ])
                        );
                        $this->addFlash('success', 'Compte créé pour ' . $firstName . ' ' . $lastName . ' et invitation envoyée.');
                    }
                }
                break;

            case 'structure':
                $structureId = (int) $request->request->get('share_with_structure_id');
                $structure = $em->getRepository(Structure::class)->find($structureId);
                if ($structure) {
                    $entry->setSharedWithStructure($structure);
                    $entry->setStatus('shared');
                    $structureEmail = $structure->getEmail();
                    if ($structureEmail) {
                        $this->sendShareEmail(
                            $structureEmail,
                            'consultation_shared',
                            array_merge($emailParams, [
                                'recipient_email' => $structureEmail,
                                'is_new_user' => false,
                            ])
                        );
                    }
                    $this->addFlash('success', 'Consultation partagée avec la structure ' . $structure->getName() . '.');
                }
                break;

            case 'new_structure':
                $structureName = $request->request->get('new_structure_name');
                $structureEmail = $request->request->get('new_structure_email');
                if ($structureName && $structureEmail) {
                    $structure = new Structure();
                    $structure->setName($structureName);
                    $structure->setEmail($structureEmail);
                    $structure->setCreatedBy($sender);
                    $em->persist($structure);
                    $em->flush();

                    $entry->setSharedWithStructure($structure);
                    $entry->setStatus('shared');

                    $registerUrl = $this->generateUrl('app_register', [], UrlGeneratorInterface::ABSOLUTE_URL);
                    $this->sendShareEmail(
                        $structureEmail,
                        'structure_invitation',
                        [
                            'sender_name' => $sender->getFullName(),
                            'structure_name' => $structureName,
                            'register_url' => $registerUrl,
                            'claim_code' => $structure->getClaimCode(),
                        ]
                    );
                    $this->addFlash('success', 'Structure "' . $structureName . '" créée et invitation envoyée.');
                }
                break;
        }
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
