<?php

namespace App\Form;

use App\Entity\Animal;
use App\Entity\HealthBookEntry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
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
        $builder
            ->add('animal', EntityType::class, [
                'label' => 'Animal concerné',
                'class' => Animal::class,
                'choice_label' => 'name',
                'placeholder' => 'Sélectionner un animal',
            ])

            ->add('title', TextType::class, [
                'label' => 'Intitulé de l’acte',
                'attr' => [
                    'placeholder' => 'Ex : Vaccin annuel, consultation, chirurgie...',
                ],
            ])

            ->add('type', ChoiceType::class, [
                'label' => 'Type d’acte',
                'placeholder' => 'Sélectionner un type d’acte',
                'choices' => [
                    'Consultation' => 'Consultation',
                    'Vaccination' => 'Vaccination',
                    'Chirurgie' => 'Chirurgie',
                    'Traitement antiparasitaire' => 'Antiparasitaire',
                    'Analyse / examen' => 'Analyse',
                    'Autre' => 'Autre',
                ],
            ])

            ->add('date', DateType::class, [
                'label' => 'Date de l’acte',
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

            ->add('attachments', FileType::class, [
                'label' => 'Documents liés à l’acte médical',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'attr' => [
                    'accept' => '.pdf,.jpg,.jpeg,.png,.webp,.doc,.docx',
                ],
                'help' => 'Exemples : compte-rendu vétérinaire, ordonnance, facture, résultat d’analyse.',
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
    }
}