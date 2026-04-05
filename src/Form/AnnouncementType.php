<?php

namespace App\Form;

use App\Entity\Announcement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AnnouncementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $inputClass = 'input-field w-full';

        $builder
            ->add('title', TextType::class, [
                'label' => 'Sarlavha',
                'attr' => [
                    'class' => $inputClass,
                    'placeholder' => 'Masalan: ✨ Pesca 2027 uchun yangi drop chiqdi',
                ],
            ])
            ->add('body', TextareaType::class, [
                'label' => 'Matn',
                'attr' => [
                    'class' => $inputClass,
                    'rows' => 6,
                    'placeholder' => 'Emoji, yangi satr va chiroyli matn bilan yozing. Qanday kiritsangiz, shunday chiqadi.',
                ],
            ])
            ->add('mediaType', ChoiceType::class, [
                'label' => 'Media turi',
                'choices' => [
                    'Media yo\'q' => Announcement::MEDIA_TYPE_NONE,
                    'Rasm' => Announcement::MEDIA_TYPE_IMAGE,
                    'Video' => Announcement::MEDIA_TYPE_VIDEO,
                ],
                'attr' => ['class' => $inputClass],
            ])
            ->add('mediaUrl', UrlType::class, [
                'label' => 'Media linki',
                'required' => false,
                'attr' => [
                    'class' => $inputClass,
                    'placeholder' => 'https://... rasm yoki video linki',
                ],
            ])
            ->add('ctaLabel', TextType::class, [
                'label' => 'Tugma matni',
                'required' => false,
                'attr' => [
                    'class' => $inputClass,
                    'placeholder' => 'Masalan: Batafsil ko\'rish',
                ],
            ])
            ->add('ctaUrl', UrlType::class, [
                'label' => 'Tugma linki',
                'required' => false,
                'attr' => [
                    'class' => $inputClass,
                    'placeholder' => 'https://... yoki sahifa linki',
                ],
            ])
            ->add('sortOrder', IntegerType::class, [
                'label' => 'Tartib raqami',
                'attr' => [
                    'class' => $inputClass,
                    'min' => 0,
                ],
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Faol e\'lon',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Announcement::class,
        ]);
    }
}
