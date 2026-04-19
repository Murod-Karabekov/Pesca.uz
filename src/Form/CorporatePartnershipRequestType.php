<?php

namespace App\Form;

use App\Entity\CorporatePartnershipRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CorporatePartnershipRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('organizationName', TextType::class, [
                'label' => 'Tashkilot nomi',
                'attr' => ['class' => 'input-field min-h-[48px]', 'autocomplete' => 'organization'],
            ])
            ->add('contactFullName', TextType::class, [
                'label' => 'Mas\'ul shaxs (ism familiya)',
                'attr' => ['class' => 'input-field min-h-[48px]', 'autocomplete' => 'name'],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Telefon',
                'attr' => ['class' => 'input-field min-h-[48px]', 'autocomplete' => 'tel', 'placeholder' => '+998 …'],
            ])
            ->add('address', TextareaType::class, [
                'label' => 'Manzil',
                'attr' => [
                    'class' => 'input-field min-h-[120px] py-3',
                    'rows' => 4,
                    'placeholder' => 'Yuridik yoki pochta manzili, filial, indeks …',
                ],
            ])
            ->add('additionalNotes', TextareaType::class, [
                'label' => 'Takliflaringiz',
                'required' => true,
                'attr' => [
                    'class' => 'input-field min-h-[140px] py-3',
                    'rows' => 5,
                    'placeholder' => 'Hamkorlik formati, muddat, kutilayotgan natija, savollar …',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CorporatePartnershipRequest::class,
        ]);
    }
}
