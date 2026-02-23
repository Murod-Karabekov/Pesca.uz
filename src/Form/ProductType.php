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
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Product Name',
                'attr' => [
                    'placeholder' => 'Enter product name',
                    'class' => 'input-field',
                ],
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Price',
                'currency' => 'UZS',
                'attr' => [
                    'placeholder' => '0.00',
                    'class' => 'input-field',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Product description...',
                    'class' => 'input-field',
                    'rows' => 4,
                ],
            ])
            ->add('size', ChoiceType::class, [
                'label' => 'Available Sizes',
                'choices' => array_combine(Product::SIZES, Product::SIZES),
                'multiple' => true,
                'expanded' => true,
                'attr' => ['class' => 'flex flex-wrap gap-3'],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Product Image',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG, or WebP).',
                    ]),
                ],
                'attr' => ['class' => 'input-field', 'accept' => 'image/*'],
            ])
            ->add('status', CheckboxType::class, [
                'label' => 'Active',
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
