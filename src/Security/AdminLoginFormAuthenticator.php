<?php

namespace App\Security;

use App\Entity\User;
use App\Enum\RoleEnum;
use App\Service\SecurityEventLogger;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class AdminLoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'admin_login';
    public const DEFAULT_REDIRECT_ROUTE = 'admin_dashboard';
    private const FIREWALL_NAME = 'admin';

    private UrlGeneratorInterface $urlGenerator;
    private SecurityEventLogger $securityEventLogger;

    public function __construct(UrlGeneratorInterface $urlGenerator, SecurityEventLogger $securityEventLogger)
    {
        $this->urlGenerator = $urlGenerator;
        $this->securityEventLogger = $securityEventLogger;
    }

    public function supports(Request $request): bool
    {
        $route = $request->attributes->get('_route');
        return $route === self::LOGIN_ROUTE && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        $this->securityEventLogger->logAdminLoginAttempt($request, self::FIREWALL_NAME);

        $email = $request->request->get('_username', '');
        $password = $request->request->get('_password', '');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            $this->securityEventLogger->logAdminLoginCriticalUserInstanceError($request, $user, $token, $firewallName);
            throw new CustomUserMessageAuthenticationException('An unexpected authentication error occurred. Please try again.');
        }

        if (!in_array(RoleEnum::EMPLOYEE->value, $user->getRoles(), true)) {
            $this->securityEventLogger->logAdminLoginDeniedForInsufficientRole($request, $user, $firewallName);
            throw new CustomUserMessageAuthenticationException('Access denied. This area is for employees only.');
        }

        if (method_exists($user, 'isActive') && !$user->isActive()) {
            $this->securityEventLogger->logAdminLoginDeniedForDisabledAccount($request, $user, $firewallName);
            throw new CustomUserMessageAuthenticationException('Your account is disabled. Please contact an administrator.');
        }

        $this->securityEventLogger->logAdminLoginSuccess($request, $token, $firewallName);

        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate(self::DEFAULT_REDIRECT_ROUTE));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE, [
            '_locale' => $request->getLocale(),
        ]);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $this->securityEventLogger->logAdminLoginFailure($request, $exception, self::FIREWALL_NAME);

        if ($request->hasSession()) {
            $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);
        }

        return new RedirectResponse($this->getLoginUrl($request));
    }
}