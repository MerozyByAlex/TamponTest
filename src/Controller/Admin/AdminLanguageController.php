<?php
// src/Controller/Admin/AdminLanguageController.php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class AdminLanguageController extends AbstractController
{
    private array $supportedLocales;
    private string $defaultAdminLocale;

    public function __construct(array $supportedLocales, string $defaultAdminLocale)
    {
        $this->supportedLocales = $supportedLocales;
        $this->defaultAdminLocale = $defaultAdminLocale;
    }

    #[Route('/admin_93576798/change-locale/{locale}', name: 'admin_change_locale')]
    public function changeLocale(string $locale, Request $request): RedirectResponse
    {
        // Sanitize and validate the chosen locale
        if (!in_array($locale, $this->supportedLocales, true)) {
            $locale = $this->defaultAdminLocale;
        }

        // Store the chosen locale in the session. C'est toujours utile.
        $request->getSession()->set('_locale', $locale);

        // ==================================================================
        // CORRECTION : On ne redirige plus vers le "referer" qui contient l'ancienne langue.
        // À la place, on redirige vers la route du tableau de bord EN FORÇANT la nouvelle langue dans l'URL.
        // ==================================================================
        return $this->redirectToRoute('admin_dashboard', ['_locale' => $locale]);

        /*
         * ANCIEN CODE QUI POSAIT PROBLÈME :
         * $referer = $request->headers->get('referer');
         * if (!$referer || str_contains($referer, $request->getPathInfo())) {
         * return $this->redirectToRoute('admin_dashboard');
         * }
         * return new RedirectResponse($referer);
        */
    }
}