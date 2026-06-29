<?php

namespace App\Form;

use App\Entity\Animal;
use App\Entity\AnimalShare;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AnimalShareType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('animal', EntityType::class, [
                'label' => 'Animal à partager',
                'class' => Animal::class,
                'choice_label' => 'name',
                'placeholder' => 'Sélectionner un animal',
            ])

            ->add('sharedWithEmail', EmailType::class, [
                'label' => 'Adresse e-mail du destinataire',
                'attr' => [
                    'placeholder' => 'exemple@email.fr',
                ],
            ])

            ->add('permissionLevel', ChoiceType::class, [
                'label' => 'Niveau d’autorisation',
                'placeholder' => 'Sélectionner une autorisation',
                'choices' => [
                    'Lecture seule' => 'READ',
                    'Modification autorisée' => 'WRITE',
                ],
            ])

            ->add('createdAt', DateType::class, [
                'label' => 'Date de création du partage',
                'widget' => 'single_text',
                'data' => new \DateTimeImmutable(),
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