<?php

namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Mahsulot nomi',
                'attr' => [
                    'placeholder' => 'Mahsulot nomini kiriting',
                    'class' => 'input-field',
                ],
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Narx',
                'currency' => 'UZS',
                'attr' => [
                    'placeholder' => '0.00',
                    'class' => 'input-field',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Tavsif',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Mahsulot tavsifi...',
                    'class' => 'input-field',
                    'rows' => 4,
                ],
            ])
            ->add('size', ChoiceType::class, [
                'label' => 'Mavjud o\'lchamlar',
                'choices' => array_combine(Product::SIZES, Product::SIZES),
                'multiple' => true,
                'expanded' => true,
                'attr' => ['class' => 'flex flex-wrap gap-3'],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Mahsulot rasmi (fayl yuklash)',
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
            ])
            ->add('imageUrl', UrlType::class, [
                'label' => 'Rasm havolasi (URL)',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'placeholder' => 'https://example.com/image.jpg',
                    'class' => 'input-field',
                ],
            ])
            ->add('status', CheckboxType::class, [
                'label' => 'Faol',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
