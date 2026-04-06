<?php

declare(strict_types=1);

namespace Guiziweb\SyliusGridAssistantPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

final class AiSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('query', TextType::class, [
                'label' => false,
                'required' => true,
                'constraints' => [
                    new NotBlank(message: 'Please enter a search query.'),
                ],
                'attr' => [
                    'placeholder' => 'Describe what you are looking for...',
                    'autocomplete' => 'off',
                ],
            ])
            ->add('grid_code', HiddenType::class)
            ->add('route_name', HiddenType::class)
            ->add('route_params', HiddenType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'grid_assistant_search',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'guiziweb_ai_search';
    }
}
