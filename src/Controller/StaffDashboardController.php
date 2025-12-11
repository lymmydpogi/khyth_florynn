<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Product;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\ServicesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/staff/dashboard', name: 'app_staff_dashboard_')]
#[IsGranted('ROLE_STAFF')]
final class StaffDashboardController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        ProductRepository $productRepository,
        ServicesRepository $servicesRepository,
        OrderRepository $orderRepository
    ): Response {
        $user = $this->getUser();

        // Staff's own products
        $myProducts = $productRepository->createQueryBuilder('p')
            ->where('p.createdBy = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        $myActiveProducts = $productRepository->createQueryBuilder('p')
            ->where('p.createdBy = :user')
            ->andWhere('p.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'active')
            ->getQuery()
            ->getResult();

        $myLowStockProducts = $productRepository->createQueryBuilder('p')
            ->where('p.createdBy = :user')
            ->andWhere('p.stock <= :threshold')
            ->andWhere('p.status = :status')
            ->setParameter('user', $user)
            ->setParameter('threshold', 5)
            ->setParameter('status', 'active')
            ->getQuery()
            ->getResult();

        // Staff's own services
        $myServices = $servicesRepository->createQueryBuilder('s')
            ->where('s.createdBy = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        $myActiveServices = $servicesRepository->createQueryBuilder('s')
            ->where('s.createdBy = :user')
            ->andWhere('s.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'active')
            ->getQuery()
            ->getResult();

        // Staff's own orders
        $myOrders = $orderRepository->createQueryBuilder('o')
            ->where('o.createdBy = :user')
            ->setParameter('user', $user)
            ->orderBy('o.orderDate', 'DESC')
            ->getQuery()
            ->getResult();

        $myPendingOrders = $orderRepository->createQueryBuilder('o')
            ->where('o.createdBy = :user')
            ->andWhere('o.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', Order::STATUS_PENDING)
            ->getQuery()
            ->getResult();

        $myCompletedOrders = $orderRepository->createQueryBuilder('o')
            ->where('o.createdBy = :user')
            ->andWhere('o.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', Order::STATUS_COMPLETED)
            ->getQuery()
            ->getResult();

        // Monthly revenue from staff's orders
        $currentDate = new \DateTime();
        $startOfMonth = (clone $currentDate)->modify('first day of this month')->setTime(0, 0, 0);
        $endOfMonth = (clone $currentDate)->modify('last day of this month')->setTime(23, 59, 59);

        $myMonthlyRevenue = (float) $orderRepository->createQueryBuilder('o')
            ->select('SUM(o.totalPrice)')
            ->where('o.createdBy = :user')
            ->andWhere('o.orderDate BETWEEN :start AND :end')
            ->setParameter('user', $user)
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // Recent orders (last 5)
        $recentOrders = $orderRepository->createQueryBuilder('o')
            ->where('o.createdBy = :user')
            ->setParameter('user', $user)
            ->orderBy('o.orderDate', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Total products value (inventory value)
        $totalInventoryValue = 0;
        foreach ($myProducts as $product) {
            if ($product->getPrice() && $product->getStock()) {
                $totalInventoryValue += $product->getPrice() * $product->getStock();
            }
        }

        return $this->render('ADMIN/staff_dashboard/index.html.twig', [
            'myProducts' => $myProducts,
            'myActiveProducts' => $myActiveProducts,
            'myLowStockProducts' => $myLowStockProducts,
            'myServices' => $myServices,
            'myActiveServices' => $myActiveServices,
            'myOrders' => $myOrders,
            'myPendingOrders' => $myPendingOrders,
            'myCompletedOrders' => $myCompletedOrders,
            'myMonthlyRevenue' => $myMonthlyRevenue,
            'recentOrders' => $recentOrders,
            'totalInventoryValue' => $totalInventoryValue,
        ]);
    }
}


