<?php

namespace App\Form;

use App\Entity\Animal;
use App\Entity\AnimalShare;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AnimalShareType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sharedWithEmail')
            ->add('permissionLevel', ChoiceType::class, [
                'choices' => [
                    'Lecture seule' => 'READ',
                    'Modification autorisée' => 'WRITE',
                ],
            ])
            ->add('createdAt', null, [
                'widget' => 'single_text',
                'data' => new \DateTimeImmutable(), // Pré-remplit automatiquement la date du jour !
            ])
            ->add('animal', EntityType::class, [
                'class' => Animal::class,
                'choice_label' => 'name', // Affiche le nom au lieu de l'ID
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AnimalShare::class,
        ]);
    }
}