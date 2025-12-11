<?php

namespace App\Form;

use App\Entity\Services;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ServicesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'] ?? false;

        $builder
            ->add('name', TextType::class, [
                'label' => 'Customization Service Name',
                'attr' => [
                    'maxlength' => 255,
                    'placeholder' => 'e.g., Wedding Arrangement Customization'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Service name is required']),
                    new Assert\Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'Service name must be at least {{ limit }} characters long',
                        'maxMessage' => 'Service name cannot be longer than {{ limit }} characters',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Service Description',
                'attr' => [
                    'rows' => 6,
                    'maxlength' => 2000,
                    'placeholder' => 'Describe what this customization service includes...'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Description is required']),
                    new Assert\Length([
                        'min' => 10,
                        'max' => 2000,
                        'minMessage' => 'Description must be at least {{ limit }} characters long',
                        'maxMessage' => 'Description cannot be longer than {{ limit }} characters',
                    ]),
                ],
            ])
            ->add('price', NumberType::class, [
                'label' => 'Price (₱)',
                'attr' => [
                    'step' => '0.01',
                    'min' => '0',
                    'placeholder' => '0.00'
                ],
                'scale' => 2,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Price is required']),
                    new Assert\Positive(['message' => 'Price must be greater than 0']),
                    new Assert\Range([
                        'min' => 0.01,
                        'max' => 999999.99,
                        'notInRangeMessage' => 'Price must be between {{ min }} and {{ max }}',
                    ]),
                ],
            ])
            ->add('pricingModel', ChoiceType::class, [
                'label' => 'Pricing Model',
                'choices' => [
                    'Fixed Price' => 'fixed',
                    'Per Hour' => 'hourly',
                    'Per Milestone' => 'milestone',
                ],
                'placeholder' => 'Select pricing model',
                'expanded' => false,
                'multiple' => false,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Pricing model is required']),
                    new Assert\Choice([
                        'choices' => ['fixed', 'hourly', 'milestone'],
                        'message' => 'Please select a valid pricing model',
                    ]),
                ],
            ])
            ->add('pricingUnit', ChoiceType::class, [
                'label' => 'Pricing Unit',
                'placeholder' => 'Select pricing unit',
                'choices' => [
                    'Per Arrangement' => 'arrangement',
                    'Per Bouquet' => 'bouquet',
                    'Per Event' => 'event',
                    'Per Customization' => 'customization',
                    'Per Hour' => 'hour',
                    'Per Day' => 'day',
                    'Per Milestone' => 'milestone',
                ],
                'choice_attr' => function($choice, $key, $value) {
                    return match ($value) {
                        'arrangement', 'bouquet', 'event', 'customization' => ['data-group' => 'fixed'],
                        'hour', 'day' => ['data-group' => 'hourly'],
                        'milestone' => ['data-group' => 'milestone'],
                        default => [],
                    };
                },
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Pricing unit is required']),
                ],
            ])
            ->add('deliveryTime', IntegerType::class, [
                'label' => 'Delivery Time (Days)',
                'attr' => [
                    'min' => '1',
                    'max' => '365',
                    'placeholder' => 'e.g., 7'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Delivery time is required']),
                    new Assert\Positive(['message' => 'Delivery time must be greater than 0']),
                    new Assert\Range([
                        'min' => 1,
                        'max' => 365,
                        'notInRangeMessage' => 'Delivery time must be between {{ min }} and {{ max }} days',
                    ]),
                ],
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Service Category',
                'choices' => [
                    'Wedding Arrangements' => 'Wedding Arrangements',
                    'Funeral Arrangements' => 'Funeral Arrangements',
                    'Event Decorations' => 'Event Decorations',
                    'Custom Bouquets' => 'Custom Bouquets',
                    'Corporate Events' => 'Corporate Events',
                    'Birthday Arrangements' => 'Birthday Arrangements',
                    'Anniversary Arrangements' => 'Anniversary Arrangements',
                    'Other' => 'Other',
                ],
                'placeholder' => 'Select category',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Category is required']),
                ],
            ])
            ->add('toolsUsed', TextType::class, [
                'label' => 'Flowers & Materials Used',
                'required' => false,
                'attr' => [
                    'maxlength' => 255,
                    'placeholder' => 'e.g., Roses, Peonies, Eucalyptus, Ribbons, Vases'
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'Flowers & materials cannot be longer than {{ limit }} characters',
                    ]),
                ],
            ])
            ->add('revisionLimit', TextType::class, [
                'label' => 'Revision Limit',
                'required' => false,
                'attr' => [
                    'maxlength' => 50,
                    'placeholder' => 'e.g., 3 (number of design revisions allowed)'
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 50,
                        'maxMessage' => 'Revision limit cannot be longer than {{ limit }} characters',
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^\d+$/',
                        'message' => 'Revision limit must be a number',
                    ]),
                ],
            ]);

        // Status only editable in edit mode
        if ($isEdit) {
            $builder->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Active' => 'active',
                    'Inactive' => 'inactive',
                ],
                'placeholder' => 'Choose Status',
                'expanded' => false,
                'multiple' => false,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Status is required']),
                    new Assert\Choice([
                        'choices' => ['active', 'inactive'],
                        'message' => 'Please select a valid status',
                    ]),
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Services::class,
            'is_edit' => false,
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}
