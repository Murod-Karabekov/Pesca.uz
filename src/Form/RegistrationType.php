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
                'label' => 'To\'liq ism',
                'attr' => [
                    'placeholder' => 'To\'liq ismingizni kiriting',
                    'class' => 'input-field',
                ],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Telefon raqami',
                'attr' => [
                    'placeholder' => '+998 XX XXX XX XX',
                    'class' => 'input-field',
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => [
                    'label' => 'Parol',
                    'attr' => [
                        'placeholder' => 'Parol yarating',
                        'class' => 'input-field',
                    ],
                ],
                'second_options' => [
                    'label' => 'Parolni tasdiqlash',
                    'attr' => [
                        'placeholder' => 'Parolingizni tasdiqlang',
                        'class' => 'input-field',
                    ],
                ],
                'invalid_message' => 'Parol maydonlari mos kelishi kerak.',
                'constraints' => [
                    new NotBlank(['message' => 'Iltimos, parol kiriting.']),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Parol kamida {{ limit }} belgidan iborat bo\'lishi kerak.',
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
