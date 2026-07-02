<?php

namespace App\Form;

use App\Entity\Animal;
use App\Entity\AnimalShare;
use App\Entity\User;
use App\Repository\AnimalRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AnimalShareType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User $user */
        $user = $options['user'];

        $builder
            ->add('animal', EntityType::class, [
                'label' => 'Animal à partager',
                'class' => Animal::class,
                'choice_label' => 'name',
                'placeholder' => 'Sélectionner un animal',
                'query_builder' => fn (AnimalRepository $repo) => $repo->createQueryBuilder('a')
                    ->where('a.owner = :user')
                    ->setParameter('user', $user),
            ])

            ->add('sharedWithEmail', EmailType::class, [
                'label' => 'Adresse e-mail du destinataire',
                'attr' => [
                    'placeholder' => 'exemple@email.fr',
                ],
            ])

            ->add('permissionLevel', ChoiceType::class, [
                'label' => 'Niveau d\'autorisation',
                'placeholder' => 'Sélectionner une autorisation',
                'choices' => [
                    'Lecture seule' => 'READ',
                    'Modification autorisée' => 'WRITE',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AnimalShare::class,
        ]);
        $resolver->setRequired('user');
        $resolver->setAllowedTypes('user', User::class);
    }
}
