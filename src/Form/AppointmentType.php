<?php

namespace App\Form;

use App\Entity\Animal;
use App\Entity\Appointment;
use App\Entity\User;
use App\Repository\AnimalRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AppointmentType extends AbstractType
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
            ->add('reason', TextType::class, [
                'label' => 'Motif du rendez-vous',
                'attr' => ['placeholder' => 'Ex : Vaccination annuelle, Contrôle post-opératoire...'],
            ])
            ->add('scheduledAt', DateTimeType::class, [
                'label' => 'Date et heure',
                'widget' => 'single_text',
            ])
            ->add('location', TextType::class, [
                'label' => 'Lieu / Cabinet',
                'required' => false,
                'attr' => ['placeholder' => 'Ex : Clinique vétérinaire des Landes'],
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => ['rows' => 3, 'placeholder' => 'Informations complémentaires...'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Appointment::class]);
        $resolver->setRequired('user');
        $resolver->setAllowedTypes('user', User::class);
    }
}
