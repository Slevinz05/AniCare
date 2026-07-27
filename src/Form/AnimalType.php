<?php

namespace App\Form;

use App\Entity\Animal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;

class AnimalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du cheval',
                'attr' => [
                    'placeholder' => 'Ex : Ourasi',
                ],
            ])

            ->add('breed', TextType::class, [
                'label' => 'Race',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex : Selle Français, Pur-sang, Trotteur...',
                ],
            ])

            ->add('coat', ChoiceType::class, [
                'label' => 'Robe',
                'required' => false,
                'placeholder' => 'Sélectionner une robe',
                'choices' => [
                    'Alezan' => 'Alezan',
                    'Bai' => 'Bai',
                    'Gris' => 'Gris',
                    'Noir' => 'Noir',
                    'Blanc' => 'Blanc',
                    'Palomino' => 'Palomino',
                    'Pie' => 'Pie',
                    'Autre' => 'Autre',
                ],
            ])

            ->add('coatCustom', TextType::class, [
                'label' => 'Précisez la robe',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex : Isabelle, Cremello...',
                ],
            ])

            ->add('birthDate', DateType::class, [
                'label' => 'Date de naissance',
                'widget' => 'single_text',
                'required' => false,
            ])

            ->add('age', IntegerType::class, [
                'label' => 'Âge (années)',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex : 8',
                    'min' => 0,
                    'max' => 50,
                ],
            ])

            ->add('gender', ChoiceType::class, [
                'label' => 'Sexe',
                'choices' => [
                    'Hongre' => 'Hongre',
                    'Entier' => 'Entier',
                    'Jument' => 'Jument',
                ],
                'expanded' => true,
                'multiple' => false,
            ])

            ->add('height', NumberType::class, [
                'label' => 'Taille',
                'required' => false,
                'scale' => 2,
                'attr' => [
                    'placeholder' => 'Ex : 1.65',
                ],
            ])

            ->add('weight', NumberType::class, [
                'label' => 'Poids actuel',
                'required' => false,
                'scale' => 2,
                'attr' => [
                    'placeholder' => 'Ex : 520',
                ],
            ])

            ->add('averageWeight', NumberType::class, [
                'label' => 'Poids moyen',
                'required' => false,
                'scale' => 2,
                'attr' => [
                    'placeholder' => 'Ex : 500',
                ],
            ])

            ->add('identificationNumber', TextType::class, [
                'label' => 'Numéro de SIRE',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex : 00123456789A',
                ],
            ])

            ->add('microchipNumber', TextType::class, [
                'label' => 'Numéro de puce',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex : 250269xxxxxxxxx',
                ],
            ])

            // --- Lieu de vie (EVO-014 à EVO-017) ---
            ->add('livingPlaceName', TextType::class, [
                'label' => 'Nom de la structure',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex : Écurie du Bois Joli',
                ],
            ])

            ->add('livingPlaceManagerLastName', TextType::class, [
                'label' => 'Nom du gérant',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex : Dupont',
                ],
            ])

            ->add('livingPlaceManagerFirstName', TextType::class, [
                'label' => 'Prénom du gérant',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex : Jean',
                ],
            ])

            ->add('livingPlaceManagerPhone', TelType::class, [
                'label' => 'Téléphone du gérant',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex : 06 12 34 56 78',
                ],
            ])

            ->add('livingPlaceManagerEmail', EmailType::class, [
                'label' => 'E-mail du gérant',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex : gerant@ecurie.fr',
                ],
            ])

            ->add('livingPlaceStreet', TextType::class, [
                'label' => 'Rue',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex : 12 chemin des Prés',
                ],
            ])

            ->add('livingPlaceComplement', TextType::class, [
                'label' => 'Complément d\'adresse',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex : Lieu-dit, bâtiment...',
                ],
            ])

            ->add('livingPlacePostalCode', TextType::class, [
                'label' => 'Code postal',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex : 33000',
                ],
            ])

            ->add('livingPlaceCity', TextType::class, [
                'label' => 'Ville',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex : Bordeaux',
                ],
            ])

            ->add('livingPlaceCountry', CountryType::class, [
                'label' => 'Pays',
                'required' => false,
                'placeholder' => 'Sélectionner un pays',
                'preferred_choices' => ['FR'],
            ])

            // --- Contact de confiance (EVO-024) ---
            ->add('trustedContactFirstName', TextType::class, [
                'label' => 'Prénom',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex : Marie',
                ],
            ])

            ->add('trustedContactLastName', TextType::class, [
                'label' => 'Nom',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex : Martin',
                ],
            ])

            ->add('trustedContactPhone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex : 06 12 34 56 78',
                ],
            ])

            ->add('photoFile', FileType::class, [
                'label' => 'Photo du cheval',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'accept' => 'image/jpeg,image/png,image/webp',
                ],
                'help' => 'Format accepté : JPG, PNG ou WebP (max 5 Mo).',
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez ajouter une image valide (JPG, PNG ou WebP).',
                    ]),
                ],
            ])

            ->add('attachments', FileType::class, [
                'label' => 'Documents du cheval',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'attr' => [
                    'accept' => '.pdf,.jpg,.jpeg,.png,.webp,.doc,.docx',
                ],
                'help' => 'Vous pouvez ajouter un ou plusieurs fichiers (Taille max : 10 Mo par fichier).',
                'constraints' => [
                    new All([
                        new File([
                            'maxSize' => '10M',
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
            'data_class' => Animal::class,
        ]);
    }
}
