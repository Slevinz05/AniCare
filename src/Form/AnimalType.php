<?php

namespace App\Form;

use App\Entity\Animal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class AnimalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('species')
            ->add('breed')
            ->add('birthDate', null, [
                'widget' => 'single_text',
            ])
            ->add('gender', ChoiceType::class, [
                'choices' => [
                    'Mâle' => 'Malle',
                    'Femelle' => 'Femelle',
                ],
                'expanded' => true, // Transforme en boutons radio plutôt qu'un menu déroulant !
            ])
            ->add('weight')
            ->add('bloodType')
            ->add('identificationNumber')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Animal::class,
        ]);
    }
}
