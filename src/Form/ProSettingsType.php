<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProSettingsType extends AbstractType
{
    private const DAYS = [
        'Lundi' => 'lundi',
        'Mardi' => 'mardi',
        'Mercredi' => 'mercredi',
        'Jeudi' => 'jeudi',
        'Vendredi' => 'vendredi',
        'Samedi' => 'samedi',
        'Dimanche' => 'dimanche',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('defaultConsultationDuration', IntegerType::class, [
                'label' => 'Durée moyenne d\'une séance (minutes)',
                'required' => false,
                'attr' => ['placeholder' => 'Ex : 60', 'min' => 5, 'max' => 480],
            ])
            ->add('defaultAppointmentNotes', TextareaType::class, [
                'label' => 'Notes par défaut',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex : Prévoir un box propre, cheval attaché...',
                    'rows' => 3,
                ],
                'help' => 'Ces notes seront pré-remplies sur chaque nouveau rendez-vous.',
            ])
            ->add('defaultPublicNotes', TextareaType::class, [
                'label' => 'Notes par défaut',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex : Tarif consultation : 80€. Recommandations post-séance...',
                    'rows' => 3,
                ],
                'help' => 'Ces notes seront pré-remplies sur chaque nouveau compte rendu.',
            ])
            ->add('directoryVisible', CheckboxType::class, [
                'label' => 'Apparaître dans l\'annuaire des professionnels',
                'required' => false,
            ])
            ->add('openingHoursVisible', CheckboxType::class, [
                'label' => 'Afficher mes horaires sur mon profil public',
                'required' => false,
            ])
        ;

        foreach (self::DAYS as $label => $key) {
            $builder
                ->add('hours_' . $key . '_enabled', CheckboxType::class, [
                    'label' => $label,
                    'mapped' => false,
                    'required' => false,
                ])
                ->add('hours_' . $key . '_start', TextType::class, [
                    'label' => false,
                    'mapped' => false,
                    'required' => false,
                    'attr' => ['placeholder' => '09:00', 'class' => 'form-control form-control-sm'],
                ])
                ->add('hours_' . $key . '_end', TextType::class, [
                    'label' => false,
                    'mapped' => false,
                    'required' => false,
                    'attr' => ['placeholder' => '18:00', 'class' => 'form-control form-control-sm'],
                ])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
