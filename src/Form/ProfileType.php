<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'required' => false,
                'attr' => ['placeholder' => 'Votre prénom'],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'required' => false,
                'attr' => ['placeholder' => 'Votre nom'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail',
                'attr' => ['placeholder' => 'votre@email.fr'],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => ['placeholder' => '06 12 34 56 78'],
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'required' => false,
                'attr' => ['placeholder' => 'Numéro et rue', 'autocomplete' => 'street-address'],
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'required' => false,
                'attr' => ['placeholder' => 'Votre ville'],
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
                'required' => false,
                'attr' => ['placeholder' => '75000', 'maxlength' => 5],
            ])
            ->add('specialty', ChoiceType::class, [
                'label' => 'Specialite',
                'required' => false,
                'placeholder' => 'Selectionnez votre specialite',
                'choices' => [
                    'Veterinaire' => 'Veterinaire',
                    'Dentiste' => 'Dentiste',
                    'Osteopathe' => 'Osteopathe',
                    'Marechal-ferrant' => 'Marechal-ferrant',
                    'Shiatsu' => 'Shiatsu',
                    'Massotherapeute' => 'Massotherapeute',
                    'Physiotherapeute' => 'Physiotherapeute',
                    'Nutritionniste' => 'Nutritionniste',
                    'Comportementaliste' => 'Comportementaliste',
                    'Autre' => 'Autre',
                ],
            ])
            ->add('bio', TextareaType::class, [
                'label' => 'Présentation courte',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Quelques mots sur vous ou votre activité...',
                    'rows' => 4,
                ],
            ])
            ->add('acceptsNotifications', CheckboxType::class, [
                'label' => 'Recevoir les notifications par e-mail (rappels, nouveaux messages)',
                'required' => false,
            ])
        ;

        if ($options['is_pro'] ?? false) {
            $builder
                ->add('profileDescription', TextareaType::class, [
                    'label' => 'Description détaillée de vos services',
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'Décrivez en détail vos prestations, votre approche, votre parcours...',
                        'rows' => 8,
                    ],
                ])
                ->add('website', UrlType::class, [
                    'label' => 'Site web',
                    'required' => false,
                    'attr' => ['placeholder' => 'https://www.monsite.fr'],
                ])
                ->add('experienceYears', IntegerType::class, [
                    'label' => "Années d'expérience",
                    'required' => false,
                    'attr' => ['placeholder' => 'Ex : 10', 'min' => 0, 'max' => 99],
                ])
                ->add('profilePhotosUpload', FileType::class, [
                    'label' => 'Photos de votre activité',
                    'mapped' => false,
                    'required' => false,
                    'multiple' => true,
                    'attr' => ['accept' => 'image/jpeg,image/png,image/webp'],
                    'help' => 'Ajoutez des photos de vos interventions, votre cabinet, vos équipements... (max 5 Mo par photo)',
                    'constraints' => [
                        new All([
                            new File([
                                'maxSize' => '5M',
                                'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                                'mimeTypesMessage' => 'Format accepté : JPG, PNG ou WebP.',
                            ]),
                        ]),
                    ],
                ])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_pro' => false,
        ]);
        $resolver->setAllowedTypes('is_pro', 'bool');
    }
}
