<?php

namespace App\Form;

use App\Entity\Animal;
use App\Entity\HealthBookEntry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HealthBookEntryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Vaccination' => 'Vaccination',
                    'Consultation' => 'Consultation',
                    'Chirurgie' => 'Chirurgie',
                    'Traitement antiparasitaire' => 'Antiparasitaire',
                    'Autre' => 'Autre',
                ],
            ])
            ->add('date', null, [
                'widget' => 'single_text',
            ])
            ->add('description')
            ->add('veterinarianName')
            ->add('animal', EntityType::class, [
                'class' => Animal::class,
                'choice_label' => 'name', // Affiche le nom de l'animal au lieu de son ID !
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