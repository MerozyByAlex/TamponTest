<?php

namespace App\Controller\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_EMPLOYEE')]
class TwoFactorEnrollmentController extends AbstractController
{
    public function __construct(
        private readonly TotpAuthenticatorInterface $totpAuthenticator,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $securityLogger
    ) {
    }

    #[Route('/admin_93576798/profile/2fa/setup', name: 'admin_2fa_setup')]
    public function setup(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->isTotpAuthenticationEnabled()) {
            $this->addFlash('info', 'L’authentification à deux facteurs est déjà activée sur votre compte.');
            return $this->redirectToRoute('admin_dashboard');
        }

        // Génère un secret TOTP
        $secret = $this->totpAuthenticator->generateSecret();
        $user->setTotpAuthenticationSecret($secret);

        if ($request->isMethod('POST')) {
            $code = $request->request->get('auth_code', '');

            if ($this->totpAuthenticator->checkCode($user, $code)) {
                $user->setIsTwoFactorAuthenticationEnabled(true);
                $this->entityManager->flush();

                $this->securityLogger->info('Activation 2FA réussie.', [
                    'user_id' => $user->getId(),
                    'username' => $user->getUserIdentifier(),
                ]);

                $this->addFlash('success', 'L’authentification à deux facteurs est maintenant activée.');
                return $this->redirectToRoute('admin_dashboard');
            }

            $this->addFlash('error', 'Le code de vérification est incorrect. Veuillez réessayer.');
        }

        // Génère manuellement l’URL pour le QR code (à encoder en PNG)
        $qrContent = $this->totpAuthenticator->getQRContent($user);
        $qrCodeDataUri = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($qrContent);

        return $this->render('security/2fa_setup.html.twig', [
            'qrCodeUri' => $qrCodeDataUri,
            'secretKey' => $secret,
        ]);
    }
}