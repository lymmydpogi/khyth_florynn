<?php

namespace App\Controller;

use App\Entity\Services;
use App\Entity\Order;
use App\Entity\Product;
use App\Form\ServicesType;
use App\Repository\ServicesRepository;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home_index')]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        ServicesRepository $servicesRepository,
        OrderRepository $orderRepository,
        UserRepository $userRepository,
        ProductRepository $productRepository
    ): Response {
        // ────────── Service Creation Form ──────────
        $service = new Services();
        $form = $this->createForm(ServicesType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($service);
            $em->flush();
            $this->addFlash('success', 'New service added successfully!');
            return $this->redirectToRoute('app_home_index');
        }

        // ────────── Analytics ──────────
        $activeServices = $servicesRepository->count([]);
        $totalUsers = $userRepository->countAllClients();
        
        // Product statistics
        $totalProducts = $productRepository->count([]);
        $activeProducts = (int) $productRepository->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.status = :status')
            ->setParameter('status', 'active')
            ->getQuery()
            ->getSingleScalarResult();
        
        $lowStockProducts = count($productRepository->findLowStock());
        $outOfStockProducts = (int) $productRepository->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.status = :status')
            ->setParameter('status', 'out_of_stock')
            ->getQuery()
            ->getSingleScalarResult();

        // Order statistics
        $pendingOrders = (int) $orderRepository->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.status = :status')
            ->setParameter('status', Order::STATUS_PENDING)
            ->getQuery()
            ->getSingleScalarResult();
        
        $completedOrders = (int) $orderRepository->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.status = :status')
            ->setParameter('status', Order::STATUS_COMPLETED)
            ->getQuery()
            ->getSingleScalarResult();
        
        $totalOrders = (int) $orderRepository->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // ────────── Monthly Revenue ──────────
        $currentDate = new \DateTime();
        $startOfMonth = (clone $currentDate)->modify('first day of this month')->setTime(0, 0, 0);
        $endOfMonth = (clone $currentDate)->modify('last day of this month')->setTime(23, 59, 59);

        $monthlyRevenue = (float) $orderRepository->createQueryBuilder('o')
            ->select('SUM(o.totalPrice)')
            ->where('o.orderDate BETWEEN :start AND :end')
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // ────────── Trend Calculation (Optional) ──────────
        $startPrevMonth = (clone $currentDate)->modify('first day of last month')->setTime(0, 0, 0);
        $endPrevMonth = (clone $currentDate)->modify('last day of last month')->setTime(23, 59, 59);

        $prevPendingOrders = (int) $orderRepository->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.status = :status')
            ->andWhere('o.orderDate BETWEEN :start AND :end')
            ->setParameter('status', Order::STATUS_PENDING)
            ->setParameter('start', $startPrevMonth)
            ->setParameter('end', $endPrevMonth)
            ->getQuery()
            ->getSingleScalarResult();

        $pendingOrdersTrend = $pendingOrders > $prevPendingOrders ? 'up' : ($pendingOrders < $prevPendingOrders ? 'down' : 'neutral');

        // ────────── Render Template ──────────
        return $this->render('ADMIN/home/index.html.twig', [
            'form' => $form->createView(),
            'services' => $servicesRepository->findAll(),
            'activeServices' => $activeServices,
            'totalProducts' => $totalProducts,
            'activeProducts' => $activeProducts,
            'lowStockProducts' => $lowStockProducts,
            'outOfStockProducts' => $outOfStockProducts,
            'pendingOrders' => $pendingOrders,
            'completedOrders' => $completedOrders,
            'totalOrders' => $totalOrders,
            'totalUsers' => $totalUsers,
            'monthlyRevenue' => $monthlyRevenue,
            'pendingOrdersTrend' => $pendingOrdersTrend,
        ]);
    }
}
