<?php

namespace App\Form;

use App\Entity\Animal;
use App\Entity\Reminder;
use App\Repository\AnimalRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReminderType extends AbstractType
{
    public function __construct(
        private readonly Security $security,
        private readonly AnimalRepository $animalRepository,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $this->security->getUser();
        $animals = $user ? $this->animalRepository->findAccessibleAnimals($user) : [];

        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre du rappel',
                'attr' => ['placeholder' => 'Ex : Vaccin grippe équine'],
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => array_flip(Reminder::CATEGORIES),
                'placeholder' => 'Choisir une catégorie',
                'required' => false,
            ])
            ->add('animal', EntityType::class, [
                'class' => Animal::class,
                'choices' => $animals,
                'choice_label' => 'name',
                'label' => 'Animal',
                'placeholder' => 'Choisir un animal',
            ])
            ->add('scheduledAt', DateType::class, [
                'label' => 'Date prévue',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('recurrence', ChoiceType::class, [
                'label' => 'Récurrence',
                'choices' => array_flip(Reminder::RECURRENCES),
                'placeholder' => 'Ponctuel (pas de récurrence)',
                'required' => false,
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => ['rows' => 3, 'placeholder' => 'Notes complémentaires...'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reminder::class,
        ]);
    }
}
