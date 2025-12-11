<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[Route('/products')]
final class ProductController extends AbstractController
{
    #[Route(name: 'app_products_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        return $this->render('ADMIN/_TABLES/products/index.html.twig', [
            'products' => $productRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_products_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $product = new Product();
        $product->setStatus('active');
        $product->setCreatedBy($this->getUser());

        $form = $this->createForm(ProductType::class, $product, [
            'is_edit' => false,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Handle file upload
                $imageFile = $form->get('imageFile')->getData();
                if ($imageFile) {
                    // Validate file type
                    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    if (!in_array($imageFile->getMimeType(), $allowedMimeTypes)) {
                        $this->addFlash('error', 'Invalid file type. Please upload a JPEG, PNG, GIF, or WebP image.');
                        return $this->render('ADMIN/_TABLES/products/new.html.twig', [
                            'product' => $product,
                            'form' => $form,
                        ]);
                    }

                    // Validate file size (max 5MB)
                    if ($imageFile->getSize() > 5 * 1024 * 1024) {
                        $this->addFlash('error', 'Image file is too large. Maximum size is 5MB.');
                        return $this->render('ADMIN/_TABLES/products/new.html.twig', [
                            'product' => $product,
                            'form' => $form,
                        ]);
                    }

                    $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                    $productsDir = $this->getParameter('products_directory');
                    if (!is_dir($productsDir)) {
                        if (!mkdir($productsDir, 0775, true)) {
                            throw new \RuntimeException('Failed to create products directory.');
                        }
                    }
                    $imageFile->move($productsDir, $newFilename);
                    $product->setImage($newFilename);
                }

                // Validate stock and status consistency
                if ($product->getStock() < 0) {
                    $this->addFlash('error', 'Stock quantity cannot be negative.');
                    return $this->render('ADMIN/_TABLES/products/new.html.twig', [
                        'product' => $product,
                        'form' => $form,
                    ]);
                }

                if ($product->getStock() <= 0) {
                    $product->setStatus('out_of_stock');
                }

                $entityManager->persist($product);
                $entityManager->flush();

                $this->addFlash('success', 'Product created successfully.');
                return $this->redirectToRoute('app_products_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Symfony\Component\HttpFoundation\File\Exception\FileException $e) {
                $this->addFlash('error', 'Failed to upload image. Please try again or contact support.');
            } catch (\Doctrine\DBAL\Exception\ConnectionException $e) {
                $this->addFlash('error', 'Database connection error. Please try again later.');
            } catch (\Exception $e) {
                error_log('Product creation error: ' . $e->getMessage());
                $this->addFlash('error', 'An unexpected error occurred while creating the product. Please try again.');
            }
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Please correct the errors in the form and try again.');
        }

        return $this->render('ADMIN/_TABLES/products/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_products_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Product $product,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        AuthorizationCheckerInterface $auth
    ): Response {
        // Staff restriction
        if (!$auth->isGranted('PRODUCT_EDIT', $product)) {
            $this->addFlash('error', 'You cannot edit this product created by an Admin.');
            return $this->redirectToRoute('app_products_index');
        }

        $form = $this->createForm(ProductType::class, $product, [
            'is_edit' => true,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Validate status consistency with stock
            if ($product->getStock() <= 0 && $product->getStatus() === 'active') {
                $product->setStatus('out_of_stock');
                $this->addFlash('warning', 'Product status was automatically changed to "out_of_stock" because stock is zero.');
            } elseif ($product->getStock() > 0 && $product->getStatus() === 'out_of_stock') {
                $product->setStatus('active');
                $this->addFlash('warning', 'Product status was automatically changed to "active" because stock is available.');
            }

            // Handle file upload if new image provided
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                // Delete old image if exists
                if ($product->getImage()) {
                    $productsDir = $this->getParameter('products_directory');
                    $oldImagePath = $productsDir . '/' . $product->getImage();
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $productsDir = $this->getParameter('products_directory');
                    if (!is_dir($productsDir)) {
                        mkdir($productsDir, 0775, true);
                    }
                    $imageFile->move($productsDir, $newFilename);
                    $product->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Failed to upload image: ' . $e->getMessage());
                }
            }

            $product->setUpdatedAt(new \DateTimeImmutable());
            
            try {
                $entityManager->flush();
                $this->addFlash('success', 'Product updated successfully.');
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            }

            return $this->redirectToRoute('app_products_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('ADMIN/_TABLES/products/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_products_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('ADMIN/_TABLES/products/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_product_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Product $product,
        EntityManagerInterface $entityManager,
        AuthorizationCheckerInterface $auth
    ): Response {
        if (!$auth->isGranted('PRODUCT_DELETE', $product)) {
            $this->addFlash('error', 'You cannot delete this product created by an Admin.');
            return $this->redirectToRoute('app_products_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->request->get('_token'))) {
            // Delete associated image
            if ($product->getImage()) {
                $productsDir = $this->getParameter('products_directory');
                $imagePath = $productsDir . '/' . $product->getImage();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            $entityManager->remove($product);
            $entityManager->flush();

            $this->addFlash('success', 'Product deleted successfully.');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('app_products_index', [], Response::HTTP_SEE_OTHER);
    }
}

