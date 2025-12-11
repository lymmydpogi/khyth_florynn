<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        Security $security,
        EntityManagerInterface $entityManager
    ): Response
    {
        // Redirect already logged-in users
       if ($this->getUser() && in_array('ROLE_ADMIN', $this->getUser()->getRoles(), true)) {
            return $this->redirectToRoute('app_home_index');
    }

        $user = new User();

        // Assign default role for new users
        $user->setRoles(['ROLE_CLIENT']);

        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Validate email uniqueness before proceeding
                $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
                if ($existingUser) {
                    $this->addFlash('error', 'This email address is already registered. Please use a different email or try logging in.');
                    return $this->render('ADMIN/registration/register.html.twig', [
                        'registrationForm' => $form->createView(),
                    ]);
                }

                // Hash password
                $plainPassword = $form->get('plainPassword')->getData();
                if (empty($plainPassword)) {
                    $this->addFlash('error', 'Password cannot be empty.');
                    return $this->render('ADMIN/registration/register.html.twig', [
                        'registrationForm' => $form->createView(),
                    ]);
                }

                $user->setPassword(
                    $passwordHasher->hashPassword($user, $plainPassword)
                );

                // Optional custom fields
                if ($form->has('name')) {
                    $user->setName($form->get('name')->getData());
                }
                if ($form->has('phone')) {
                    $user->setPhone($form->get('phone')->getData());
                }
                if ($form->has('address')) {
                    $user->setAddress($form->get('address')->getData());
                }

                // Persist user
                $entityManager->persist($user);
                $entityManager->flush();

                // Redirect to login (NO auto login)
                $this->addFlash('success', 'Account created successfully! Please log in with your credentials.');
                return $this->redirectToRoute('app_login_index');
            } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
                $this->addFlash('error', 'This email address is already registered. Please use a different email or try logging in.');
            } catch (\Doctrine\DBAL\Exception\ConnectionException $e) {
                $this->addFlash('error', 'Database connection error. Please try again later or contact support.');
            } catch (\Exception $e) {
                // Log the error for debugging (in production, log to file)
                error_log('Registration error: ' . $e->getMessage());
                $this->addFlash('error', 'An unexpected error occurred while creating your account. Please try again or contact support if the problem persists.');
            }
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            // Collect all validation errors
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            
            // Show first error as flash message
            if (!empty($errors)) {
                $this->addFlash('error', $errors[0]);
            } else {
                $this->addFlash('error', 'Please correct the errors in the form and try again.');
            }
        }

        return $this->render('ADMIN/registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
