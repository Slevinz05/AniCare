<?php

namespace App\Form;

use App\Entity\Allergy;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AllergyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Allergène',
                'attr' => ['placeholder' => 'Ex : Poulet, Pollen, Pénicilline...'],
            ])
            ->add('severity', ChoiceType::class, [
                'label' => 'Sévérité',
                'choices' => [
                    'Légère' => 'MILD',
                    'Modérée' => 'MODERATE',
                    'Sévère' => 'SEVERE',
                ],
                'placeholder' => 'Sélectionner',
            ])
            ->add('symptoms', TextareaType::class, [
                'label' => 'Symptômes observés',
                'required' => false,
                'attr' => ['rows' => 2, 'placeholder' => 'Démangeaisons, vomissements, gonflement...'],
            ])
            ->add('diagnosedAt', DateType::class, [
                'label' => 'Date de diagnostic',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes complémentaires',
                'required' => false,
                'attr' => ['rows' => 2],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Allergy::class]);
    }
}
