<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login_index')]
    public function login(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        // Redirect already logged-in users
        if ($this->getUser()) {
            $roles = $this->getUser()->getRoles();
            if (in_array('ROLE_ADMIN', $roles, true)) {
                return $this->redirectToRoute('app_home_index');
            } elseif (in_array('ROLE_STAFF', $roles, true)) {
                return $this->redirectToRoute('app_staff_dashboard_index');
            }
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        $accessDenied = $request->query->get('access_denied', false);

        // Handle specific authentication errors with user-friendly messages
        if ($error) {
            $errorMessage = $this->getErrorMessage($error);
            $this->addFlash('error', $errorMessage);
        }

        // Handle access denied messages
        if ($accessDenied) {
            $this->addFlash('warning', 'You do not have permission to access that page.');
        }

        return $this->render('ADMIN/security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'access_denied' => $accessDenied
        ]);
    }

    /**
     * Convert authentication error to user-friendly message
     */
    private function getErrorMessage($error): string
    {
        $errorMessageKey = $error->getMessageKey();
        $errorData = $error->getMessageData();

        // Map Symfony security error keys to user-friendly messages
        $errorMessages = [
            'Invalid credentials.' => 'Invalid email or password. Please check your credentials and try again.',
            'Bad credentials.' => 'Invalid email or password. Please check your credentials and try again.',
            'User account is disabled.' => 'Your account has been disabled. Please contact an administrator.',
            'User account is locked.' => 'Your account has been temporarily locked. Please try again later or contact support.',
            'User account has expired.' => 'Your account has expired. Please contact an administrator.',
            'Credentials have expired.' => 'Your credentials have expired. Please reset your password.',
            'Account is disabled.' => 'Your account is currently disabled. Please contact support for assistance.',
            'Invalid CSRF token.' => 'Security verification failed. Please refresh the page and try again.',
        ];

        // Check if we have a direct match
        if (isset($errorMessages[$errorMessageKey])) {
            return $errorMessages[$errorMessageKey];
        }

        // Check for partial matches
        foreach ($errorMessages as $key => $message) {
            if (stripos($errorMessageKey, $key) !== false || stripos($key, $errorMessageKey) !== false) {
                return $message;
            }
        }

        // Default fallback message
        return 'Authentication failed. Please check your credentials and try again.';
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
