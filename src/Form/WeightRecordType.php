<?php

namespace App\Form;

use App\Entity\WeightRecord;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WeightRecordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('weight', NumberType::class, [
                'label' => 'Poids (kg)',
                'scale' => 2,
                'attr' => ['placeholder' => 'Ex : 12.5'],
            ])
            ->add('recordedAt', DateType::class, [
                'label' => 'Date de la pesée',
                'widget' => 'single_text',
            ])
            ->add('note', TextareaType::class, [
                'label' => 'Note',
                'required' => false,
                'attr' => ['rows' => 2, 'placeholder' => 'Observation (ex : après stérilisation)...'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => WeightRecord::class]);
    }
}
