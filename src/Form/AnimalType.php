<?php

namespace App\Form;

use App\Entity\Animal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
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
                'label' => 'Nom de l’animal',
                'attr' => [
                    'placeholder' => 'Ex : Hatchi',
                ],
            ])

            ->add('species', TextType::class, [
                'label' => 'Espèce',
                'attr' => [
                    'placeholder' => 'Ex : Chien, chat, lapin...',
                ],
            ])

            ->add('breed', TextType::class, [
                'label' => 'Race',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex : Shiba Inu, Européen...',
                ],
            ])

            ->add('birthDate', DateType::class, [
                'label' => 'Date de naissance',
                'widget' => 'single_text',
                'required' => false,
            ])

            ->add('gender', ChoiceType::class, [
                'label' => 'Sexe',
                'choices' => [
                    'Mâle' => 'Mâle',
                    'Femelle' => 'Femelle',
                ],
                'expanded' => true,
                'multiple' => false,
            ])

            ->add('weight', NumberType::class, [
                'label' => 'Poids',
                'required' => false,
                'scale' => 2,
                'attr' => [
                    'placeholder' => 'Ex : 12.5',
                ],
            ])

            ->add('bloodType', TextType::class, [
                'label' => 'Groupe sanguin',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex : DEA 1.1, A, B...',
                ],
            ])

            ->add('identificationNumber', TextType::class, [
                'label' => 'Numéro d’identification',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Numéro de puce électronique ou de tatouage',
                ],
            ])

            ->add('attachments', FileType::class, [
                'label' => 'Documents de l’animal',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'attr' => [
                    'accept' => '.pdf,.jpg,.jpeg,.png,.webp,.doc,.docx',
                ],
                'help' => 'Vous pouvez ajouter un ou plusieurs fichiers : PDF, images ou documents Word.',
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
            'data_class' => Animal::class,
        ]);
    }
}