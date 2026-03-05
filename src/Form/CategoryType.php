<?php

namespace App\Form;

use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\String\Slugger\AsciiSlugger;

class CategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Kategoriya nomi',
                'attr' => [
                    'placeholder' => 'Masalan: Xalatlar',
                    'class' => 'input-field',
                ],
            ])
            ->add('slug', TextType::class, [
                'label' => 'Slug (URL uchun)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Avtomatik yaratiladi',
                    'class' => 'input-field',
                ],
            ])
            ->add('icon', TextType::class, [
                'label' => 'Emoji icon',
                'required' => false,
                'attr' => [
                    'placeholder' => '🥼',
                    'class' => 'input-field',
                ],
            ]);

        // Avtomatik slug yaratish
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $category = $event->getData();
            if (!$category->getSlug() && $category->getName()) {
                $slugger = new AsciiSlugger();
                $category->setSlug(strtolower($slugger->slug($category->getName())));
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
        ]);
    }
}
