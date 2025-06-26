<?php

namespace App\Controller\Security;

use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class TwoFactorController extends AbstractController
{
    /**
     * Affiche le formulaire de saisie du code 2FA (TOTP).
     * Cette route est définie dans `security.yaml` comme `auth_form_path`.
     */
    #[Route(path: '/admin_93576798/2fa', name: '2fa_login')]
    public function showTwoFactorForm(Request $request): Response
    {
        $session = $request->getSession();
        $error = $session->get(Security::AUTHENTICATION_ERROR);
        $session->remove(Security::AUTHENTICATION_ERROR); // Nettoyage après récupération

        return $this->render('security/2fa_login.html.twig', [
            'error' => $error,
        ]);
    }

    /**
     * Traite le code 2FA soumis par l'utilisateur.
     * Cette route est interceptée automatiquement par le bundle.
     */
    #[Route(path: '/admin_93576798/2fa_check', name: '2fa_login_check')]
    public function checkTwoFactorCode(): void
    {
        throw new \LogicException('Cette méthode ne devrait jamais être appelée directement : la requête est interceptée par le listener de sécurité du bundle 2FA.');
    }
}