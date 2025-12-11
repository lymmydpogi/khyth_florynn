<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Product;
use App\Entity\Services;
use App\Entity\Order;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Create Admin User
        $admin = new User();
        $admin->setEmail('khyth@gmail.com');
        $admin->setName('khyth Ovalo');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setStatus('active');
        $admin->setPassword(
            $this->passwordHasher->hashPassword(
                $admin,
                'adminuser1'
            )
        );
        $manager->persist($admin);

        // Create Staff User
        $staff = new User();
        $staff->setEmail('staff@florynn.com');
        $staff->setName('janpol');
        $staff->setRoles(['ROLE_STAFF']);
        $staff->setStatus('active');
        $staff->setPassword(
            $this->passwordHasher->hashPassword(
                $staff,
                'staff123'
            )
        );
        $manager->persist($staff);

        // Create Client Users (at least 3)
        $clients = [
            ['email' => 'juan.delacruz@email.com', 'name' => 'Juan Dela Cruz', 'phone' => '+63 912 345 6789'],
            ['email' => 'maria.garcia@email.com', 'name' => 'juan dela criz', 'phone' => '+63 923 456 7890'],
            ['email' => 'carlos.reyes@email.com', 'name' => 'Juan dela craz', 'phone' => '+63 934 567 8901'],
        ];

        $clientUsers = [];
        foreach ($clients as $clientData) {
            $client = new User();
            $client->setEmail($clientData['email']);
            $client->setName($clientData['name']);
            $client->setPhone($clientData['phone']);
            $client->setRoles(['ROLE_CLIENT']);
            $client->setStatus('active');
            $client->setPassword(
                $this->passwordHasher->hashPassword(
                    $client,
                    'client123'
                )
            );
            $manager->persist($client);
            $clientUsers[] = $client;
        }

        $manager->flush();

        // Create Products (at least 3)
        $products = [
            [
                'name' => 'Red Rose Bouquet',
                'description' => 'Beautiful arrangement of 12 fresh red roses, elegantly wrapped with green foliage. Perfect for expressing love and romance.',
                'price' => 1500.00,
                'category' => 'Bouquet',
                'stock' => 25,
                'status' => 'active',
            ],
            [
                'name' => 'White Lily Arrangement',
                'description' => 'Elegant white lilies arranged in a classic vase. Ideal for weddings, funerals, or as a sophisticated gift.',
                'price' => 2000.00,
                'category' => 'Arrangement',
                'stock' => 15,
                'status' => 'active',
            ],
            [
                'name' => 'Mixed Spring Bouquet',
                'description' => 'Colorful mix of seasonal flowers including tulips, daisies, and baby\'s breath. Brings joy and freshness to any space.',
                'price' => 1200.00,
                'category' => 'Bouquet',
                'stock' => 30,
                'status' => 'active',
            ],
            [
                'name' => 'Sunflower Gift Set',
                'description' => 'Bright and cheerful sunflowers with a decorative vase. Perfect for birthdays and celebrations.',
                'price' => 1800.00,
                'category' => 'Gift Set',
                'stock' => 8,
                'status' => 'active',
            ],
        ];

        $productEntities = [];
        foreach ($products as $productData) {
            $product = new Product();
            $product->setName($productData['name']);
            $product->setDescription($productData['description']);
            $product->setPrice($productData['price']);
            $product->setCategory($productData['category']);
            $product->setStock($productData['stock']);
            $product->setStatus($productData['status']);
            $product->setCreatedBy($admin);
            $product->setCreatedAt(new \DateTimeImmutable());
            $manager->persist($product);
            $productEntities[] = $product;
        }

        $manager->flush();

        // Create Services (Customization Services - at least 3)
        $services = [
            [
                'name' => 'Wedding Arrangement Customization',
                'description' => 'Custom wedding flower arrangements tailored to your theme and color scheme. Includes consultation, design, and setup.',
                'price' => 15000.00,
                'pricingModel' => 'fixed',
                'pricingUnit' => 'arrangement',
                'deliveryTime' => 7,
                'category' => 'Wedding Arrangements',
                'toolsUsed' => 'Roses, Peonies, Eucalyptus, Ribbons, Vases',
                'revisionLimit' => '3',
                'status' => 'active',
            ],
            [
                'name' => 'Custom Bouquet Design',
                'description' => 'Personalized bouquet design service. Choose your flowers, colors, and style. Perfect for special occasions.',
                'price' => 2500.00,
                'pricingModel' => 'fixed',
                'pricingUnit' => 'bouquet',
                'deliveryTime' => 3,
                'category' => 'Custom Bouquets',
                'toolsUsed' => 'Selected Flowers, Wrapping Paper, Ribbons, Decorative Elements',
                'revisionLimit' => '2',
                'status' => 'active',
            ],
            [
                'name' => 'Event Decoration Service',
                'description' => 'Complete event decoration with floral arrangements. Includes centerpieces, entrance arrangements, and venue decoration.',
                'price' => 25000.00,
                'pricingModel' => 'fixed',
                'pricingUnit' => 'event',
                'deliveryTime' => 14,
                'category' => 'Event Decorations',
                'toolsUsed' => 'Various Flowers, Stands, Vases, Decorative Elements',
                'revisionLimit' => '2',
                'status' => 'active',
            ],
            [
                'name' => 'Funeral Arrangement Service',
                'description' => 'Respectful and elegant funeral flower arrangements. Available in various sizes and styles to honor your loved one.',
                'price' => 5000.00,
                'pricingModel' => 'fixed',
                'pricingUnit' => 'arrangement',
                'deliveryTime' => 1,
                'category' => 'Funeral Arrangements',
                'toolsUsed' => 'White Lilies, Roses, Chrysanthemums, Ribbons',
                'revisionLimit' => '1',
                'status' => 'active',
            ],
        ];

        $serviceEntities = [];
        foreach ($services as $serviceData) {
            $service = new Services();
            $service->setName($serviceData['name']);
            $service->setDescription($serviceData['description']);
            $service->setPrice($serviceData['price']);
            $service->setPricingModel($serviceData['pricingModel']);
            $service->setPricingUnit($serviceData['pricingUnit']);
            $service->setDeliveryTime($serviceData['deliveryTime']);
            $service->setCategory($serviceData['category']);
            $service->setToolsUsed($serviceData['toolsUsed']);
            $service->setRevisionLimit($serviceData['revisionLimit']);
            $service->setStatus($serviceData['status']);
            $service->setCreatedBy($admin);
            $manager->persist($service);
            $serviceEntities[] = $service;
        }

        $manager->flush();

        // Create Orders (at least 3)
        $orders = [
            [
                'clientName' => 'Juan Dela Cruz',
                'clientEmail' => 'juan.delacruz@email.com',
                'orderDate' => new \DateTimeImmutable('-5 days'),
                'deliveryDate' => new \DateTime('+2 days'),
                'status' => Order::STATUS_PENDING,
                'paymentStatus' => Order::PAYMENT_STATUS_PENDING,
                'paymentMethod' => Order::PAYMENT_CASH,
                'notes' => 'Please deliver in the morning',
                'user' => $clientUsers[0],
                'service' => $serviceEntities[1],
                'createdBy' => $staff,
            ],
            [
                'clientName' => 'Maria Garcia',
                'clientEmail' => 'maria.garcia@email.com',
                'orderDate' => new \DateTimeImmutable('-10 days'),
                'deliveryDate' => new \DateTime('-3 days'),
                'status' => Order::STATUS_COMPLETED,
                'paymentStatus' => Order::PAYMENT_STATUS_COMPLETED, // Must be completed for completed orders
                'paymentMethod' => Order::PAYMENT_CREDIT_CARD,
                'notes' => 'Wedding arrangement for garden wedding',
                'user' => $clientUsers[1],
                'service' => $serviceEntities[0],
                'createdBy' => $admin,
            ],
            [
                'clientName' => 'Carlos Reyes',
                'clientEmail' => 'carlos.reyes@email.com',
                'orderDate' => new \DateTimeImmutable('-2 days'),
                'deliveryDate' => new \DateTime('+5 days'),
                'status' => Order::STATUS_PENDING,
                'paymentStatus' => Order::PAYMENT_STATUS_PENDING,
                'paymentMethod' => Order::PAYMENT_CASH,
                'notes' => 'Event decoration for birthday party',
                'user' => $clientUsers[2],
                'service' => $serviceEntities[2],
                'createdBy' => $staff,
            ],
            [
                'clientName' => 'Juan Dela Cruz',
                'clientEmail' => 'juan.delacruz@email.com',
                'orderDate' => new \DateTimeImmutable('-15 days'),
                'deliveryDate' => new \DateTime('-12 days'),
                'status' => Order::STATUS_COMPLETED,
                'paymentStatus' => Order::PAYMENT_STATUS_COMPLETED,
                'paymentMethod' => Order::PAYMENT_CASH,
                'notes' => 'Funeral arrangement',
                'user' => $clientUsers[0],
                'service' => $serviceEntities[3],
                'createdBy' => $admin,
            ],
        ];

        foreach ($orders as $orderData) {
            $order = new Order();
            $order->setClientName($orderData['clientName']);
            $order->setClientEmail($orderData['clientEmail']);
            $order->setOrderDate($orderData['orderDate']);
            $order->setDeliveryDate($orderData['deliveryDate']);
            
            // IMPORTANT: Set payment method and status BEFORE order status
            // This is required because completed orders must have completed payment
            $order->setPaymentMethod($orderData['paymentMethod']);
            $order->setPaymentStatus($orderData['paymentStatus']);
            
            // Now set order status (validation checks payment status)
            $order->setStatus($orderData['status']);
            
            $order->setNotes($orderData['notes']);
            $order->setUser($orderData['user']);
            $order->setService($orderData['service']);
            $order->setCreatedBy($orderData['createdBy']);
            $order->setTotalPrice($orderData['service']->getPrice());
            $manager->persist($order);
        }

        $manager->flush();
    }
}
