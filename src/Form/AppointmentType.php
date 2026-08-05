<?php

namespace App\Form;

use App\Entity\Animal;
use App\Entity\Appointment;
use App\Entity\User;
use App\Repository\AnimalRepository;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
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
        $isPro = $options['is_pro'];

        $builder
            ->add('eventType', ChoiceType::class, [
                'label' => false,
                'choices' => [
                    'Rendez-vous' => Appointment::TYPE_APPOINTMENT,
                    'Temps de repos' => Appointment::TYPE_REST,
                    'Formation / RDV personnel' => Appointment::TYPE_PERSONAL,
                ],
                'expanded' => true,
            ])
            ->add('reason', TextType::class, [
                'label' => 'Motif',
                'attr' => ['placeholder' => 'Ex : Vaccination annuelle, Contrôle post-opératoire...'],
            ])
            ->add('scheduledAt', DateTimeType::class, [
                'label' => 'Date et heure de début',
                'widget' => 'single_text',
            ])
            ->add('duration', IntegerType::class, [
                'label' => 'Durée (minutes)',
                'attr' => ['min' => 5, 'max' => 480, 'step' => 5],
            ])
            ->add('location', TextType::class, [
                'label' => 'Lieu du rendez-vous',
                'required' => false,
                'attr' => ['placeholder' => 'Nom de la structure ou adresse...'],
            ])
        ;

        if ($isPro) {
            $builder
                ->add('client', EntityType::class, [
                    'label' => 'Client',
                    'class' => User::class,
                    'choice_label' => fn (User $u) => $u->getFullName() . ' (' . $u->getEmail() . ')',
                    'placeholder' => 'Sélectionner un client',
                    'required' => false,
                    'query_builder' => fn (UserRepository $repo) => $repo->createQueryBuilder('u')
                        ->innerJoin('u.animals', 'a')
                        ->where('u.id != :self')
                        ->setParameter('self', $user->getId())
                        ->groupBy('u.id')
                        ->orderBy('u.lastName', 'ASC'),
                ])
                ->add('animals', EntityType::class, [
                    'label' => 'Chevaux concernés',
                    'class' => Animal::class,
                    'choice_label' => 'name',
                    'multiple' => true,
                    'expanded' => true,
                    'required' => false,
                    'query_builder' => fn (AnimalRepository $repo) => $repo->createQueryBuilder('a')
                        ->orderBy('a.name', 'ASC'),
                ])
                ->add('consultationType', ChoiceType::class, [
                    'label' => 'Type de consultation',
                    'required' => false,
                    'placeholder' => 'Sélectionner...',
                    'choices' => [
                        'Ostéopathie' => 'Ostéopathie',
                        'Vaccination' => 'Vaccination',
                        'Dentisterie' => 'Dentisterie',
                        'Maréchalerie' => 'Maréchalerie',
                        'Contrôle général' => 'Contrôle général',
                        'Chirurgie' => 'Chirurgie',
                        'Radiologie' => 'Radiologie',
                        'Échographie' => 'Échographie',
                        'Shiatsu / Massage' => 'Shiatsu / Massage',
                        'Comportement' => 'Comportement',
                        'Nutrition' => 'Nutrition',
                        'Urgence' => 'Urgence',
                        'Autre' => 'Autre',
                    ],
                ])
                ->add('sharedWithProfessional', EntityType::class, [
                    'label' => 'Partager avec un professionnel',
                    'class' => User::class,
                    'choice_label' => fn (User $u) => $u->getFullName() . ($u->getSpecialty() ? ' — ' . $u->getSpecialty() : ''),
                    'placeholder' => 'Aucun',
                    'required' => false,
                    'query_builder' => fn (UserRepository $repo) => $repo->createQueryBuilder('u')
                        ->where('u.accountType = :pro')
                        ->andWhere('u.id != :self')
                        ->setParameter('pro', 'PRO')
                        ->setParameter('self', $user->getId())
                        ->orderBy('u.lastName', 'ASC'),
                ])
                ->add('publicNotes', TextareaType::class, [
                    'label' => 'Notes publiques',
                    'required' => false,
                    'attr' => ['rows' => 3, 'placeholder' => 'Tarifs, consignes, détails visibles par le client...'],
                    'help' => 'Visibles par le client et les professionnels associés.',
                ])
                ->add('privateNotes', TextareaType::class, [
                    'label' => 'Notes privées',
                    'required' => false,
                    'attr' => ['rows' => 3, 'placeholder' => 'Notes personnelles, non visibles par le client...'],
                    'help' => 'Visibles uniquement par vous.',
                ])
            ;
        } else {
            $builder
                ->add('animal', EntityType::class, [
                    'label' => 'Cheval concerné',
                    'class' => Animal::class,
                    'choice_label' => 'name',
                    'placeholder' => 'Sélectionner un cheval',
                    'mapped' => false,
                    'query_builder' => fn (AnimalRepository $repo) => $repo->createQueryBuilder('a')
                        ->leftJoin('a.animalShares', 's')
                        ->where('a.owner = :user')
                        ->orWhere('s.sharedWithEmail = :email')
                        ->setParameter('user', $user)
                        ->setParameter('email', $user->getEmail())
                        ->groupBy('a.id'),
                ])
                ->add('publicNotes', TextareaType::class, [
                    'label' => 'Notes',
                    'required' => false,
                    'attr' => ['rows' => 3, 'placeholder' => 'Informations complémentaires...'],
                ])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Appointment::class,
            'is_pro' => false,
        ]);
        $resolver->setRequired('user');
        $resolver->setAllowedTypes('user', User::class);
        $resolver->setAllowedTypes('is_pro', 'bool');
    }
}
