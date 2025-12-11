<?php

namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'] ?? false;

        $builder
            ->add('name', TextType::class, [
                'label' => 'Product Name',
                'attr' => ['maxlength' => 255, 'placeholder' => 'e.g., Red Rose Bouquet'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Product name is required']),
                    new Assert\Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'Product name must be at least {{ limit }} characters long',
                        'maxMessage' => 'Product name cannot be longer than {{ limit }} characters',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['rows' => 6, 'maxlength' => 2000, 'placeholder' => 'Describe the product...'],
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
                'attr' => ['step' => '0.01', 'min' => '0', 'placeholder' => '0.00'],
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
            ->add('category', ChoiceType::class, [
                'label' => 'Category',
                'choices' => [
                    'Bouquet' => 'Bouquet',
                    'Arrangement' => 'Arrangement',
                    'Single Flower' => 'Single Flower',
                    'Wedding' => 'Wedding',
                    'Funeral' => 'Funeral',
                    'Event' => 'Event',
                    'Gift Set' => 'Gift Set',
                    'Other' => 'Other',
                ],
                'placeholder' => 'Select a category',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Category is required']),
                    new Assert\Choice([
                        'choices' => ['Bouquet', 'Arrangement', 'Single Flower', 'Wedding', 'Funeral', 'Event', 'Gift Set', 'Other'],
                        'message' => 'Please select a valid category',
                    ]),
                ],
            ])
            ->add('stock', IntegerType::class, [
                'label' => 'Stock Quantity',
                'attr' => ['min' => '0', 'placeholder' => '0'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Stock quantity is required']),
                    new Assert\PositiveOrZero(['message' => 'Stock quantity cannot be negative']),
                ],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Product Image',
                'mapped' => false,
                'required' => !$isEdit,
                'attr' => ['accept' => 'image/*'],
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                        'mimeTypesMessage' => 'Please upload a valid image file (JPEG, PNG, GIF, or WebP)',
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
                    'Out of Stock' => 'out_of_stock',
                ],
                'placeholder' => 'Choose Status',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Status is required']),
                    new Assert\Choice([
                        'choices' => ['active', 'inactive', 'out_of_stock'],
                        'message' => 'Please select a valid status',
                    ]),
                ],
            ]);
        }

        // ───────────── POST_SUBMIT Event: Validate status consistency ─────────────
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            /** @var Product $product */
            $product = $event->getData();
            $form = $event->getForm();

            // Validate status consistency with stock
            if ($product->getStock() <= 0 && $product->getStatus() === 'active') {
                $form->get('status')->addError(
                    new FormError('Products with zero stock cannot be set to active. Status will be automatically set to out_of_stock.')
                );
                // Auto-correct the status
                $product->setStatus('out_of_stock');
            }

            if ($product->getStock() > 0 && $product->getStatus() === 'out_of_stock') {
                $form->get('status')->addError(
                    new FormError('Products with stock available cannot be set to out_of_stock. Status will be automatically set to active.')
                );
                // Auto-correct the status
                $product->setStatus('active');
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
            'is_edit' => false,
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}

