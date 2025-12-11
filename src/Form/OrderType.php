<?php

namespace App\Form;

use App\Entity\Order;
use App\Entity\Services;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Options to control field visibility
        $showStatus = $options['show_status'] ?? false;
        $showPaymentMethod = $options['show_payment_method'] ?? true;

        $builder
            // ───────────── Client dropdown ─────────────
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'name',
                'placeholder' => 'Select Client',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->where('u.roles LIKE :role')
                        ->setParameter('role', '%"ROLE_CLIENT"%')
                        ->orderBy('u.name', 'ASC');
                },
            ])

            // ───────────── Order and Delivery dates ─────────────
            ->add('orderDate', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Order Date',
                'attr' => [
                    'min' => (new \DateTime())->format('Y-m-d'),
                ],
            ])
            ->add('deliveryDate', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'label' => 'Delivery Date',
            ])

            // ───────────── Notes ─────────────
            ->add('notes', TextareaType::class, [
                'required' => false,
                'attr' => ['rows' => 3],
            ])

            // ───────────── Service selection ─────────────
            ->add('service', EntityType::class, [
                'class' => Services::class,
                'choice_label' => 'name',
                'placeholder' => 'Select Service',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('s')
                        ->where('s.status = :status')
                        ->setParameter('status', 'active')
                        ->orderBy('s.name', 'ASC');
                },
                'choice_attr' => function ($service) {
                    return ['data-price' => $service->getPrice()];
                },
            ])

            // ───────────── Total price (readonly) ─────────────
            ->add('totalPrice', NumberType::class, [
                'attr' => ['readonly' => true],
                'label' => 'Total Price (PHP)',
                'scale' => 2,
            ]);

        // ───────────── Payment Method (conditional) ─────────────
        if ($showPaymentMethod) {
            $builder->add('paymentMethod', ChoiceType::class, [
                'choices' => array_combine(Order::PAYMENT_METHODS, Order::PAYMENT_METHODS),
                'placeholder' => 'Select Payment Method',
                'label' => 'Payment Method',
            ]);
        }

        // ───────────── Status and Payment Status ─────────────
        if ($showStatus) {
            $builder
                ->add('status', ChoiceType::class, [
                    'choices' => array_combine(Order::STATUSES, Order::STATUSES),
                    'label' => 'Order Status',
                ])
                ->add('paymentStatus', ChoiceType::class, [
                    'choices' => array_combine(Order::PAYMENT_STATUSES, Order::PAYMENT_STATUSES),
                    'label' => 'Payment Status',
                ]);
        }

        // ───────────── POST_SUBMIT Event: validation & auto-calc ─────────────
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($options) {
            /** @var Order $order */
            $order = $event->getData();
            $form = $event->getForm();

            // Order date cannot be before today (for new orders or when editing)
            if ($order->getOrderDate()) {
                $today = new \DateTimeImmutable('today');
                if ($order->getOrderDate() < $today) {
                    $form->get('orderDate')->addError(
                        new FormError('Order date cannot be set before today.')
                    );
                }
            }

            // Delivery date cannot be earlier than order date
            if ($order->getDeliveryDate() && $order->getOrderDate()) {
                // Convert DateTime to DateTimeImmutable for comparison
                $deliveryDate = $order->getDeliveryDate();
                $orderDate = $order->getOrderDate();
                
                // Convert DateTime to DateTimeImmutable if needed
                if ($deliveryDate instanceof \DateTime) {
                    $deliveryDateImmutable = \DateTimeImmutable::createFromMutable($deliveryDate);
                    if ($deliveryDateImmutable < $orderDate) {
                        $form->get('deliveryDate')->addError(
                            new FormError('Delivery date cannot be earlier than order date.')
                        );
                    }
                }
            }

            // Validate order status vs payment status consistency
            if ($order->getStatus() === Order::STATUS_COMPLETED && 
                $order->getPaymentStatus() === Order::PAYMENT_STATUS_PENDING) {
                $form->get('status')->addError(
                    new FormError('Completed orders must have completed payment status.')
                );
            }

            if ($order->getStatus() === Order::STATUS_CANCELED && 
                $order->getPaymentStatus() === Order::PAYMENT_STATUS_COMPLETED) {
                $form->get('status')->addError(
                    new FormError('Canceled orders cannot have completed payment status.')
                );
            }

            // Auto-calc total price
            if ($order->getService()) {
                $order->setTotalPrice($order->getService()->getPrice());
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
            'show_status' => false, // default = hide status & payment status fields
            'show_payment_method' => true, // default = show payment method
        ]);
    }
}