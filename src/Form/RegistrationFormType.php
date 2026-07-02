<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail',
                'attr' => [
                    'placeholder' => 'votre@email.fr',
                    'autocomplete' => 'email',
                ],
            ])

            ->add('plainPassword', PasswordType::class, [
                'label' => 'Mot de passe',
                'mapped' => false,
                'attr' => [
                    'placeholder' => 'Choisissez un mot de passe sécurisé',
                    'autocomplete' => 'new-password',
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'Veuillez saisir un mot de passe.',
                    ),
                    new Length(
                        min: 12,
                        minMessage: 'Votre mot de passe doit contenir au moins {{ limit }} caractères.',
                        max: 4096,
                    ),
                    new Regex(
                        pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/',
                        message: 'Le mot de passe doit contenir au moins une majuscule, une minuscule et un chiffre.',
                    ),
                ],
            ])

            ->add('accountType', ChoiceType::class, [
                'label' => 'Type de profil',
                'choices'  => [
                    'Je suis un Particulier (Propriétaire d\'animal)' => 'OWNER',
                    'Je suis un Professionnel (Vétérinaire, Soignant, Éducateur...)' => 'PRO',
                ],
                'expanded' => true,
                'multiple' => false,
                'constraints' => [
                    new NotBlank(
                        message: 'Veuillez sélectionner un type de profil pour continuer.',
                    ),
                ],
            ])

            ->add('acceptsNotifications', CheckboxType::class, [
                'label' => 'Recevoir les rappels et notifications par e-mail',
                'required' => false,
                'data' => true,
            ])

            ->add('agreeTerms', CheckboxType::class, [
                'label' => 'J\'accepte les conditions d\'utilisation',
                'mapped' => false,
                'constraints' => [
                    new IsTrue(
                        message: 'Vous devez accepter les conditions d\'utilisation.',
                    ),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
