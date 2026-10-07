<?php

namespace App\Controller;

use App\Entity\Animal;
use App\Entity\User;
use App\Form\ChangePasswordType;
use App\Form\ProfileType;
use App\Form\ProSettingsType;
use App\Service\DocumentUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\AsciiSlugger;

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

    #[Route('/parametres-pro/{tab}', name: 'app_profile_pro_settings', defaults: ['tab' => 'profil'], methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_PRO')]
    public function proSettings(Request $request, EntityManagerInterface $em, string $tab): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $section = $request->request->get('_section', $tab);

            if ($section === 'profil') {
                $user->setCompanyName($request->request->get('company_name'));
                $user->setSiren($request->request->get('siren'));
                $user->setShowCompanyName((bool) $request->request->get('show_company_name'));
                $user->setWebsite($request->request->get('website'));
                $user->setProfileDescription($request->request->get('profile_description'));

                $locations = json_decode($request->request->get('consultation_locations_data', '[]'), true) ?: [];
                $user->setConsultationLocations($locations);

                $departments = json_decode($request->request->get('intervention_departments_data', '[]'), true) ?: [];
                $user->setInterventionDepartments($departments);

                $user->setDirectoryVisible((bool) $request->request->get('directory_visible'));
                $user->setOpeningHoursVisible((bool) $request->request->get('opening_hours_visible'));
            }

            if ($section === 'agenda') {
                $agenda = [
                    'default_duration' => (int) ($request->request->get('default_duration') ?: 60),
                    'time_between' => (int) ($request->request->get('time_between') ?: 15),
                    'allow_custom_duration' => (bool) $request->request->get('allow_custom_duration'),
                    'visible_by_client' => (bool) $request->request->get('visible_by_client'),
                    'send_notes_on_confirm' => (bool) $request->request->get('send_notes_on_confirm'),
                    'default_notes' => $request->request->get('default_appointment_notes', ''),
                    'notes_visibility' => $request->request->get('notes_visibility', 'shared'),
                    'notify_on_confirm' => (bool) $request->request->get('notify_on_confirm'),
                    'send_reminders' => (bool) $request->request->get('send_reminders'),
                    'reminder_channels' => $request->request->all('reminder_channels') ?: ['email'],
                    'reminder_before_days' => (int) ($request->request->get('reminder_before_days') ?: 1),
                    'reminder_before_unit' => $request->request->get('reminder_before_unit', 'jour'),
                    'notify_me' => (bool) $request->request->get('notify_me_reminder'),
                    'allow_cancellation' => (bool) $request->request->get('allow_cancellation'),
                    'cancel_min_hours' => (int) ($request->request->get('cancel_min_hours') ?: 24),
                    'cancel_min_unit' => $request->request->get('cancel_min_unit', 'heures'),
                    'notify_me_cancel' => (bool) $request->request->get('notify_me_cancel'),
                    'booking_enabled' => (bool) $request->request->get('booking_enabled'),
                    'booking_who' => $request->request->get('booking_who', 'clients'),
                    'booking_species' => $request->request->all('booking_species') ?: [],
                ];
                $user->setAgendaSettings($agenda);
                $user->setDefaultConsultationDuration($agenda['default_duration']);
                $user->setDefaultAppointmentNotes($agenda['default_notes']);

                $days = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
                $hours = [];
                foreach ($days as $day) {
                    $enabled = (bool) $request->request->get('hours_' . $day . '_enabled');
                    $hours[$day] = [
                        'enabled' => $enabled,
                        'start' => $enabled ? ($request->request->get('hours_' . $day . '_start') ?: '09:00') : null,
                        'end' => $enabled ? ($request->request->get('hours_' . $day . '_end') ?: '18:00') : null,
                    ];
                }
                $user->setOpeningHours($hours);
            }

            if ($section === 'comptes-rendus') {
                $report = [
                    'show_name' => (bool) $request->request->get('report_show_name'),
                    'show_company' => (bool) $request->request->get('report_show_company'),
                    'show_phone' => (bool) $request->request->get('report_show_phone'),
                    'show_email' => (bool) $request->request->get('report_show_email'),
                    'show_website' => (bool) $request->request->get('report_show_website'),
                    'show_siren' => (bool) $request->request->get('report_show_siren'),
                    'show_antecedents' => (bool) $request->request->get('report_show_antecedents'),
                    'default_species' => $request->request->get('default_species', 'Cheval'),
                    'default_reminder_months' => (int) ($request->request->get('default_reminder_months') ?: 12),
                    'default_reminder_unit' => $request->request->get('default_reminder_unit', 'années'),
                    'auto_recurrence' => (bool) $request->request->get('auto_recurrence'),
                    'recurrence_months' => (int) ($request->request->get('recurrence_months') ?: 12),
                    'recurrence_unit' => $request->request->get('recurrence_unit', 'années'),
                    'notify_referent_sms' => (bool) $request->request->get('notify_referent_sms'),
                    'notify_referent_email' => (bool) $request->request->get('notify_referent_email'),
                    'notify_before_unit' => $request->request->get('notify_before_unit', '7 jours'),
                    'notify_me_sms' => (bool) $request->request->get('notify_me_sms'),
                    'notify_me_email' => (bool) $request->request->get('notify_me_email'),
                    'default_public_notes' => $request->request->get('default_public_notes', ''),
                ];
                $user->setReportSettings($report);
                $user->setDefaultPublicNotes($report['default_public_notes']);

                $rehabJson = $request->request->get('rehabilitation_templates_data', '[]');
                $user->setRehabilitationTemplates(json_decode($rehabJson, true) ?: []);

                $colleaguesJson = $request->request->get('recommended_colleagues_data', '[]');
                $user->setRecommendedColleagues(json_decode($colleaguesJson, true) ?: []);

                $supplementsJson = $request->request->get('supplements_data', '[]');
                $user->setSupplements(json_decode($supplementsJson, true) ?: []);
            }

            $em->flush();
            $this->addFlash('success', 'Paramètres enregistrés.');

            return $this->redirectToRoute('app_profile_pro_settings', ['tab' => $section]);
        }

        return $this->render('profile/pro_settings.html.twig', [
            'tab' => $tab,
        ]);
    }

    #[Route('/switch-space', name: 'app_switch_space', methods: ['POST'])]
    public function switchSpace(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user->hasBothSpaces()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('switch_space', $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_home');
        }

        $newSpace = $user->isInProSpace() ? 'particulier' : 'professionnel';
        $user->setActiveSpace($newSpace);
        $em->flush();

        return $this->redirectToRoute('app_home');
    }

    #[Route('/ouvrir-espace-particulier', name: 'app_enable_particulier_space', methods: ['GET', 'POST'])]
    public function enableParticulierSpace(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->hasBothSpaces() || !$user->hasProSpace()) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('enable_particulier', $request->request->get('_token'))) {
                $this->addFlash('danger', 'Token CSRF invalide.');
                return $this->redirectToRoute('app_enable_particulier_space');
            }

            $user->enableParticulierSpace();

            $animalName = trim($request->request->get('animal_name', ''));
            $animal = null;
            if ($animalName !== '') {
                $animal = new Animal();
                $animal->setName($animalName);
                $animal->setOwner($user);
                $animal->setSpecies($request->request->get('animal_species', 'Cheval'));

                $breed = trim($request->request->get('animal_breed', ''));
                if ($breed !== '') {
                    $animal->setBreed($breed);
                }

                $gender = $request->request->get('animal_gender', '');
                if (in_array($gender, ['male', 'femelle', 'hongre'], true)) {
                    $animal->setGender($gender);
                } else {
                    $animal->setGender('male');
                }

                $birthDateStr = $request->request->get('animal_birth_date', '');
                if ($birthDateStr !== '') {
                    try {
                        $animal->setBirthDate(new \DateTimeImmutable($birthDateStr));
                    } catch (\Exception) {}
                }

                $slugger = new AsciiSlugger();
                $baseSlug = strtolower($slugger->slug($animalName)->toString());
                $slug = $baseSlug . '-' . uniqid();
                $animal->setSlug($slug);

                $em->persist($animal);
            }

            $em->flush();

            if ($animal) {
                $this->addFlash('success', sprintf(
                    'Votre espace particulier est actif et la fiche de %s a été créée !',
                    $animal->getName()
                ));
                $user->setActiveSpace('particulier');
                $em->flush();
                return $this->redirectToRoute('app_animal_show', ['slug' => $animal->getSlug()]);
            }

            $this->addFlash('success', 'Votre espace particulier est maintenant actif. Vous pouvez basculer entre vos deux espaces.');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('profile/enable_particulier.html.twig');
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
