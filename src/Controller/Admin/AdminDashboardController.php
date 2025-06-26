<?php
// src/Controller/Admin/AdminDashboardController.php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_EMPLOYEE')] // Good practice to secure the whole controller
class AdminDashboardController extends AbstractController
{
    /**
     * Displays the main admin dashboard page.
     */
    #[Route('/admin_93576798/dashboard', name: 'admin_dashboard')]
    public function index(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // We check if the user has 2FA enabled and pass this info to the template.
        $isTwoFactorEnabled = $user->isTotpAuthenticationEnabled();

        return $this->render('admin/dashboard/index.html.twig', [
            'page_title' => 'Admin Dashboard',
            'is_2fa_enabled' => $isTwoFactorEnabled, // Pass the status to Twig
        ]);
    }

    /**
     * Redirects from the base admin prefix to the main admin dashboard.
     */
    #[Route('/admin_93576798', name: 'admin_home_redirect')]
    public function homeRedirect(): RedirectResponse
    {
        return $this->redirectToRoute('admin_dashboard');
    }
}