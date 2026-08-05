<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'required' => false,
                'attr' => ['placeholder' => 'Votre prénom'],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'required' => false,
                'attr' => ['placeholder' => 'Votre nom'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail',
                'attr' => ['placeholder' => 'votre@email.fr'],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => ['placeholder' => '06 12 34 56 78'],
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'required' => false,
                'attr' => ['placeholder' => 'Numéro et rue', 'autocomplete' => 'street-address'],
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'required' => false,
                'attr' => ['placeholder' => 'Votre ville'],
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
                'required' => false,
                'attr' => ['placeholder' => '75000', 'maxlength' => 5],
            ])
            ->add('specialty', ChoiceType::class, [
                'label' => 'Specialite',
                'required' => false,
                'placeholder' => 'Selectionnez votre specialite',
                'choices' => [
                    'Veterinaire' => 'Veterinaire',
                    'Dentiste' => 'Dentiste',
                    'Osteopathe' => 'Osteopathe',
                    'Marechal-ferrant' => 'Marechal-ferrant',
                    'Shiatsu' => 'Shiatsu',
                    'Massotherapeute' => 'Massotherapeute',
                    'Physiotherapeute' => 'Physiotherapeute',
                    'Nutritionniste' => 'Nutritionniste',
                    'Comportementaliste' => 'Comportementaliste',
                    'Autre' => 'Autre',
                ],
            ])
            ->add('bio', TextareaType::class, [
                'label' => 'Présentation courte',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Quelques mots sur vous ou votre activité...',
                    'rows' => 4,
                ],
            ])
            ->add('acceptsNotifications', CheckboxType::class, [
                'label' => 'Recevoir les notifications par e-mail (rappels, nouveaux messages)',
                'required' => false,
            ])
        ;

        if ($options['is_pro'] ?? false) {
            $builder
                ->add('profileDescription', TextareaType::class, [
                    'label' => 'Description détaillée de vos services',
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'Décrivez en détail vos prestations, votre approche, votre parcours...',
                        'rows' => 8,
                    ],
                ])
                ->add('website', UrlType::class, [
                    'label' => 'Site web',
                    'required' => false,
                    'attr' => ['placeholder' => 'https://www.monsite.fr'],
                ])
                ->add('interventionDepartments', ChoiceType::class, [
                    'label' => "Departements d'exercice",
                    'required' => false,
                    'multiple' => true,
                    'expanded' => false,
                    'attr' => ['class' => 'form-select', 'size' => 8],
                    'help' => 'Maintenez Ctrl (ou Cmd) pour selectionner plusieurs departements.',
                    'choices' => [
                        '01 - Ain' => '01', '02 - Aisne' => '02', '03 - Allier' => '03',
                        '04 - Alpes-de-Haute-Provence' => '04', '05 - Hautes-Alpes' => '05',
                        '06 - Alpes-Maritimes' => '06', '07 - Ardeche' => '07', '08 - Ardennes' => '08',
                        '09 - Ariege' => '09', '10 - Aube' => '10', '11 - Aude' => '11',
                        '12 - Aveyron' => '12', '13 - Bouches-du-Rhone' => '13', '14 - Calvados' => '14',
                        '15 - Cantal' => '15', '16 - Charente' => '16', '17 - Charente-Maritime' => '17',
                        '18 - Cher' => '18', '19 - Correze' => '19', '2A - Corse-du-Sud' => '2A',
                        '2B - Haute-Corse' => '2B', '21 - Cote-d\'Or' => '21', '22 - Cotes-d\'Armor' => '22',
                        '23 - Creuse' => '23', '24 - Dordogne' => '24', '25 - Doubs' => '25',
                        '26 - Drome' => '26', '27 - Eure' => '27', '28 - Eure-et-Loir' => '28',
                        '29 - Finistere' => '29', '30 - Gard' => '30', '31 - Haute-Garonne' => '31',
                        '32 - Gers' => '32', '33 - Gironde' => '33', '34 - Herault' => '34',
                        '35 - Ille-et-Vilaine' => '35', '36 - Indre' => '36', '37 - Indre-et-Loire' => '37',
                        '38 - Isere' => '38', '39 - Jura' => '39', '40 - Landes' => '40',
                        '41 - Loir-et-Cher' => '41', '42 - Loire' => '42', '43 - Haute-Loire' => '43',
                        '44 - Loire-Atlantique' => '44', '45 - Loiret' => '45', '46 - Lot' => '46',
                        '47 - Lot-et-Garonne' => '47', '48 - Lozere' => '48',
                        '49 - Maine-et-Loire' => '49', '50 - Manche' => '50', '51 - Marne' => '51',
                        '52 - Haute-Marne' => '52', '53 - Mayenne' => '53', '54 - Meurthe-et-Moselle' => '54',
                        '55 - Meuse' => '55', '56 - Morbihan' => '56', '57 - Moselle' => '57',
                        '58 - Nievre' => '58', '59 - Nord' => '59', '60 - Oise' => '60',
                        '61 - Orne' => '61', '62 - Pas-de-Calais' => '62', '63 - Puy-de-Dome' => '63',
                        '64 - Pyrenees-Atlantiques' => '64', '65 - Hautes-Pyrenees' => '65',
                        '66 - Pyrenees-Orientales' => '66', '67 - Bas-Rhin' => '67',
                        '68 - Haut-Rhin' => '68', '69 - Rhone' => '69', '70 - Haute-Saone' => '70',
                        '71 - Saone-et-Loire' => '71', '72 - Sarthe' => '72', '73 - Savoie' => '73',
                        '74 - Haute-Savoie' => '74', '75 - Paris' => '75',
                        '76 - Seine-Maritime' => '76', '77 - Seine-et-Marne' => '77',
                        '78 - Yvelines' => '78', '79 - Deux-Sevres' => '79', '80 - Somme' => '80',
                        '81 - Tarn' => '81', '82 - Tarn-et-Garonne' => '82', '83 - Var' => '83',
                        '84 - Vaucluse' => '84', '85 - Vendee' => '85', '86 - Vienne' => '86',
                        '87 - Haute-Vienne' => '87', '88 - Vosges' => '88', '89 - Yonne' => '89',
                        '90 - Territoire de Belfort' => '90', '91 - Essonne' => '91',
                        '92 - Hauts-de-Seine' => '92', '93 - Seine-Saint-Denis' => '93',
                        '94 - Val-de-Marne' => '94', '95 - Val-d\'Oise' => '95',
                        '971 - Guadeloupe' => '971', '972 - Martinique' => '972',
                        '973 - Guyane' => '973', '974 - La Reunion' => '974', '976 - Mayotte' => '976',
                    ],
                ])
                ->add('experienceYears', IntegerType::class, [
                    'label' => "Années d'expérience",
                    'required' => false,
                    'attr' => ['placeholder' => 'Ex : 10', 'min' => 0, 'max' => 99],
                ])
                ->add('defaultConsultationDuration', IntegerType::class, [
                    'label' => 'Durée par défaut d\'une consultation (minutes)',
                    'required' => false,
                    'attr' => ['placeholder' => 'Ex : 60', 'min' => 5, 'max' => 480],
                    'help' => 'Cette durée sera pré-remplie lors de la création d\'un rendez-vous.',
                ])
                ->add('defaultPublicNotes', TextareaType::class, [
                    'label' => 'Notes publiques par défaut',
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'Ex : Tarif consultation : 80€. Prévoir un box propre...',
                        'rows' => 3,
                    ],
                    'help' => 'Ces notes seront pré-remplies sur chaque nouveau rendez-vous.',
                ])
                ->add('profilePhotosUpload', FileType::class, [
                    'label' => 'Photos de votre activité',
                    'mapped' => false,
                    'required' => false,
                    'multiple' => true,
                    'attr' => ['accept' => 'image/jpeg,image/png,image/webp'],
                    'help' => 'Ajoutez des photos de vos interventions, votre cabinet, vos équipements... (max 5 Mo par photo)',
                    'constraints' => [
                        new All([
                            new File([
                                'maxSize' => '5M',
                                'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                                'mimeTypesMessage' => 'Format accepté : JPG, PNG ou WebP.',
                            ]),
                        ]),
                    ],
                ])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_pro' => false,
        ]);
        $resolver->setAllowedTypes('is_pro', 'bool');
    }
}
