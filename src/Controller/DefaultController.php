<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DefaultController extends AbstractController
{
    #[Route('/', name: 'app_default')]
    public function index(): Response
    {
        // If user is logged in, redirect based on role
        if ($this->getUser()) {
            $roles = $this->getUser()->getRoles();
            
            if (in_array('ROLE_ADMIN', $roles, true)) {
                return $this->redirectToRoute('app_home_index');
            }
            
            if (in_array('ROLE_STAFF', $roles, true)) {
                return $this->redirectToRoute('app_staff_dashboard_index');
            }
        }
        
        // Not logged in, redirect to login
        return $this->redirectToRoute('app_login_index');
    }
}


