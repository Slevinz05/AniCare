<?php

namespace App\Form;

use App\Entity\Animal;
use App\Entity\Appointment;
use App\Entity\HealthBookEntry;
use App\Entity\User;
use App\Repository\AppointmentRepository;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use App\Form\DataTransformer\JsonArrayTransformer;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;

class HealthBookEntryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User $user */
        $user = $options['user'];

        $builder
            ->add('animal', EntityType::class, [
                'label' => 'Cheval concerné',
                'class' => Animal::class,
                'choice_label' => 'name',
                'placeholder' => 'Sélectionner un cheval',
                'required' => false,
            ])

            ->add('title', TextType::class, [
                'label' => 'Intitulé de la consultation',
                'attr' => [
                    'placeholder' => 'Ex : Vaccin annuel, consultation, chirurgie...',
                ],
            ])

            ->add('type', ChoiceType::class, [
                'label' => "Type d'acte",
                'placeholder' => "Sélectionner un type d'acte",
                'choices' => [
                    'Vaccin' => 'Vaccin',
                    'Vermifuge' => 'Vermifuge',
                    'Visite d\'achat' => 'Visite d\'achat',
                    'Bilan sanguin' => 'Bilan sanguin',
                    'Ostéopathe' => 'Ostéopathe',
                    'Dentiste' => 'Dentiste',
                    'Vétérinaire' => 'Vétérinaire',
                    'Shiatsu' => 'Shiatsu',
                    'Massothérapeute' => 'Massothérapeute',
                    'Physiothérapeute' => 'Physiothérapeute',
                    'Autre' => 'Autre',
                ],
            ])

            ->add('typeCustom', TextType::class, [
                'label' => "Précisez le type d'acte",
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex : Maréchal-ferrant, Chiropracteur...',
                ],
            ])

            ->add('date', DateTimeType::class, [
                'label' => 'Date et heure',
                'widget' => 'single_text',
            ])

            ->add('appointment', EntityType::class, [
                'label' => 'Rendez-vous associé',
                'class' => Appointment::class,
                'required' => false,
                'placeholder' => 'Aucun rendez-vous',
                'choice_label' => fn (Appointment $a) => $a->getScheduledAt()->format('d/m/Y H:i') . ' — ' . $a->getReason(),
                'choice_attr' => fn (Appointment $a) => [
                    'data-date' => $a->getScheduledAt()->format('Y-m-d'),
                    'data-hour' => $a->getScheduledAt()->format('H'),
                    'data-minute' => $a->getScheduledAt()->format('i'),
                ],
                'query_builder' => function (AppointmentRepository $repo) use ($user, $options) {
                    $qb = $repo->createQueryBuilder('a')
                        ->where('a.createdBy = :user')
                        ->andWhere('a.scheduledAt < :now OR a.id = :presetId')
                        ->setParameter('user', $user)
                        ->setParameter('now', new \DateTimeImmutable())
                        ->setParameter('presetId', $options['preset_appointment_id'] ?? 0)
                        ->orderBy('a.scheduledAt', 'DESC');
                    return $qb;
                },
            ])

            ->add('veterinarian', EntityType::class, [
                'label' => 'Intervenant',
                'class' => User::class,
                'choice_label' => fn (User $u) => $u->getFullName() . ($u->getSpecialty() ? ' (' . $u->getSpecialty() . ')' : ''),
                'placeholder' => 'Selectionner un intervenant',
                'required' => false,
                'query_builder' => fn (UserRepository $repo) => $repo->createProfessionalQueryBuilder()
                    ->orderBy('u.lastName', 'ASC'),
            ])

            ->add('anamnesis', TextareaType::class, [
                'label' => 'Anamnèse / Commémoratif',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Historique du patient, antécédents, motif de consultation...',
                ],
            ])

            ->add('description', TextareaType::class, [
                'label' => 'Notes / Observations',
                'required' => false,
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Notes, observations, recommandations...',
                ],
            ])

            ->add('staticExamination', TextareaType::class, [
                'label' => 'Bilan de l\'examen statique / palpation',
                'required' => false,
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Aspect général, état de santé, position antalgique, conformation, aplombs, déformations, sensibilités...',
                ],
            ])

            ->add('rehabilitationType', ChoiceType::class, [
                'label' => 'Type de rééducation',
                'required' => false,
                'placeholder' => 'Sélectionner un type de rééducation',
                'choices' => $options['rehabilitation_templates'],
            ])

            ->add('rehabilitation', TextareaType::class, [
                'label' => 'Rééducation',
                'required' => false,
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Programme de rééducation, exercices, consignes...',
                ],
            ])

            ->add('colleagueRecommendation', TextareaType::class, [
                'label' => 'Confrère recommandé',
                'required' => false,
            ])

            ->add('dosage', TextType::class, [
                'label' => 'Posologie',
                'required' => false,
                'attr' => ['placeholder' => 'Ex : 1 comprimé de 50mg'],
            ])

            ->add('frequency', TextType::class, [
                'label' => 'Fréquence',
                'required' => false,
                'attr' => ['placeholder' => 'Ex : 2 fois par jour'],
            ])

            ->add('endDate', DateType::class, [
                'label' => 'Date de fin du traitement',
                'widget' => 'single_text',
                'required' => false,
            ])

            ->add('nextReminderAt', DateType::class, [
                'label' => 'Prochain rappel',
                'widget' => 'single_text',
                'required' => false,
            ])

            ->add('recurrenceType', ChoiceType::class, [
                'label' => 'Recurrence du rappel',
                'required' => false,
                'placeholder' => 'Pas de recurrence',
                'choices' => [
                    'Journalier' => 'daily',
                    'Hebdomadaire' => 'weekly',
                    'Bimensuel' => 'bimonthly',
                    'Mensuel' => 'monthly',
                    'Annuel' => 'annual',
                ],
            ])

            ->add('batchNumber', TextType::class, [
                'label' => 'Numéro de lot (vaccin)',
                'required' => false,
                'attr' => ['placeholder' => 'Ex : AB1234'],
            ])

            ->add('anatomicalLocations', HiddenType::class, [
                'required' => false,
                'attr' => ['id' => 'anatomical-locations-input'],
            ])

            ->add('attachments', FileType::class, [
                'label' => 'Documents liés à la consultation',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'attr' => [
                    'accept' => '.pdf,.jpg,.jpeg,.png,.webp,.doc,.docx',
                ],
                'help' => 'Exemples : compte-rendu vétérinaire, ordonnance, facture, résultat d\'analyse.',
                'constraints' => [
                    new All([
                        new File([
                            'maxSize' => '5M',
                            'mimeTypes' => [
                                'application/pdf',
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            ],
                            'mimeTypesMessage' => 'Veuillez ajouter un fichier valide : PDF, image ou document Word.',
                        ]),
                    ]),
                ],
            ])
        ;

        $builder->get('anatomicalLocations')->addModelTransformer(new JsonArrayTransformer());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => HealthBookEntry::class,
        ]);
        $resolver->setRequired('user');
        $resolver->setAllowedTypes('user', User::class);
        $resolver->setDefault('rehabilitation_templates', []);
        $resolver->setDefault('preset_appointment_id', 0);
    }
}
