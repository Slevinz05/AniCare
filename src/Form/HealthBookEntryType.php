<?php

namespace App\Form;

use App\Entity\Animal;
use App\Entity\HealthBookEntry;
use App\Entity\User;
use App\Repository\AnimalRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
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
                'label' => 'Animal concerné',
                'class' => Animal::class,
                'choice_label' => 'name',
                'placeholder' => 'Sélectionner un animal',
                'query_builder' => fn (AnimalRepository $repo) => $repo->createQueryBuilder('a')
                    ->leftJoin('a.animalShares', 's')
                    ->where('a.owner = :user')
                    ->orWhere('s.sharedWithEmail = :email')
                    ->setParameter('user', $user)
                    ->setParameter('email', $user->getEmail()),
            ])

            ->add('title', TextType::class, [
                'label' => 'Intitulé de l\'acte',
                'attr' => [
                    'placeholder' => 'Ex : Vaccin annuel, consultation, chirurgie...',
                ],
            ])

            ->add('type', ChoiceType::class, [
                'label' => 'Type d\'acte',
                'placeholder' => 'Sélectionner un type d\'acte',
                'choices' => [
                    'Consultation' => 'Consultation',
                    'Vaccination' => 'Vaccination',
                    'Traitement' => 'Traitement',
                    'Chirurgie' => 'Chirurgie',
                    'Traitement antiparasitaire' => 'Antiparasitaire',
                    'Analyse / examen' => 'Analyse',
                    'Autre' => 'Autre',
                ],
            ])

            ->add('date', DateType::class, [
                'label' => 'Date de l\'acte',
                'widget' => 'single_text',
            ])

            ->add('veterinarianName', TextType::class, [
                'label' => 'Vétérinaire référent',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex : Dupont',
                ],
            ])

            ->add('description', TextareaType::class, [
                'label' => 'Description / observations',
                'required' => false,
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Ajoutez les observations, traitements ou recommandations importantes.',
                ],
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

            ->add('recurrenceMonths', ChoiceType::class, [
                'label' => 'Récurrence du rappel',
                'required' => false,
                'placeholder' => 'Pas de récurrence',
                'choices' => [
                    '3 mois' => 3,
                    '6 mois' => 6,
                    '1 an' => 12,
                    '2 ans' => 24,
                    '3 ans' => 36,
                ],
            ])

            ->add('batchNumber', TextType::class, [
                'label' => 'Numéro de lot (vaccin)',
                'required' => false,
                'attr' => ['placeholder' => 'Ex : AB1234'],
            ])

            ->add('attachments', FileType::class, [
                'label' => 'Documents liés à l\'acte médical',
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
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => HealthBookEntry::class,
        ]);
        $resolver->setRequired('user');
        $resolver->setAllowedTypes('user', User::class);
    }
}
