<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullName', TextType::class, [
                'label' => 'Full Name',
                'attr' => [
                    'placeholder' => 'Enter your full name',
                    'class' => 'input-field',
                ],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Phone Number',
                'attr' => [
                    'placeholder' => '+998 XX XXX XX XX',
                    'class' => 'input-field',
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => [
                    'label' => 'Password',
                    'attr' => [
                        'placeholder' => 'Create a password',
                        'class' => 'input-field',
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirm Password',
                    'attr' => [
                        'placeholder' => 'Confirm your password',
                        'class' => 'input-field',
                    ],
                ],
                'invalid_message' => 'The password fields must match.',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a password.']),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Password must be at least {{ limit }} characters.',
                        'max' => 4096,
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
