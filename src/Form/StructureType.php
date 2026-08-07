<?php

namespace App\Form;

use App\Entity\Structure;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StructureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la structure',
                'attr' => ['placeholder' => 'Ex : Écurie du Soleil, Centre Équestre de la Vallée...'],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de structure',
                'required' => false,
                'placeholder' => 'Sélectionner un type',
                'choices' => [
                    'Centre équestre' => 'Centre équestre',
                    'Écurie de propriétaires' => 'Écurie de propriétaires',
                    'Écurie de compétition' => 'Écurie de compétition',
                    'Haras' => 'Haras',
                    'Pré / Pâture' => 'Pré',
                    'Clinique vétérinaire' => 'Clinique vétérinaire',
                    'Autre' => 'Autre',
                ],
            ])
            ->add('street', TextType::class, [
                'label' => 'Adresse',
                'required' => false,
                'attr' => ['placeholder' => 'Numéro et rue'],
            ])
            ->add('complement', TextType::class, [
                'label' => 'Complément d\'adresse',
                'required' => false,
                'attr' => ['placeholder' => 'Lieu-dit, bâtiment...'],
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
                'required' => false,
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'required' => false,
            ])
            ->add('country', TextType::class, [
                'label' => 'Pays',
                'required' => false,
                'attr' => ['placeholder' => 'France'],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
            ])
            ->add('email', EmailType::class, [
                'label' => 'E-mail de la structure',
                'required' => false,
            ])
            ->add('siret', TextType::class, [
                'label' => 'SIRET',
                'required' => false,
                'attr' => ['placeholder' => 'Optionnel'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Structure::class,
        ]);
    }
}
