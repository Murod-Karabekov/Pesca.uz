<?php

namespace App\Form;

use App\Entity\Tailor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class TailorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Tikuvchi ismi',
                'attr' => [
                    'placeholder' => 'Tikuvchi ismini kiriting',
                    'class' => 'input-field',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Tajriba va tavsif',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Tajribasini tasvirlang...',
                    'class' => 'input-field',
                    'rows' => 4,
                ],
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Xizmat narxi (ixtiyoriy)',
                'currency' => 'UZS',
                'required' => false,
                'attr' => [
                    'placeholder' => '0.00',
                    'class' => 'input-field',
                ],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Profil rasmi',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Iltimos, haqiqiy rasm yuklang (JPEG, PNG yoki WebP).',
                    ]),
                ],
                'attr' => ['class' => 'input-field', 'accept' => 'image/*'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tailor::class,
        ]);
    }
}
