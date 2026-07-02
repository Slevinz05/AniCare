<?php

namespace App\Form;

use App\Entity\Message;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class MessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    'rows' => 2,
                    'placeholder' => 'Votre message...',
                    'class' => 'form-control',
                ],
            ])
            ->add('attachmentFile', FileType::class, [
                'label' => false,
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control form-control-sm',
                    'accept' => '.pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.txt',
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '10M',
                        'mimeTypes' => [
                            'application/pdf',
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'text/plain',
                        ],
                        'mimeTypesMessage' => 'Format non supporté. Formats acceptés : PDF, images, Word, texte.',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Message::class]);
    }
}
