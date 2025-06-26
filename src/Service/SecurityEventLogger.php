<?php
// src/Service/SecurityEventLogger.php

namespace App\Service;

use App\Entity\User;
use App\Enum\RoleEnum; // Use the RoleEnum class
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * A dedicated service for logging security-related events.
 */
class SecurityEventLogger
{
    private LoggerInterface $securityLogger;

    public function __construct(LoggerInterface $securityLogger)
    {
        $this->securityLogger = $securityLogger;
    }

    private function getRequestContext(Request $request): array
    {
        return [
            'ip_address' => $request->getClientIp(),
            'user_agent' => $request->headers->get('User-Agent'),
            'session_id' => $request->hasSession() ? $request->getSession()->getId() : 'N/A',
        ];
    }

    public function logAdminLoginAttempt(Request $request, string $firewallName): void
    {
        $email = $request->request->get('_username', '');
        $requestContext = $this->getRequestContext($request);
        $passwordIsProvided = !empty($request->request->get('_password'));

        if (empty($email) || !$passwordIsProvided) {
            $this->securityLogger->notice(
                'Admin login attempt with missing credentials.',
                array_merge($requestContext, [
                    'email_provided' => $email ?: 'not_provided',
                    'password_provided' => $passwordIsProvided ? 'yes' : 'no',
                    'firewall' => $firewallName,
                ])
            );
        }

        $this->securityLogger->info(
            'Admin login attempt initiated.',
            array_merge($requestContext, [
                'email_provided' => $email,
                'firewall' => $firewallName,
            ])
        );
    }

    public function logAdminLoginSuccess(Request $request, TokenInterface $token, string $firewallName): void
    {
        /** @var User|null $user */
        $user = $token->getUser();
        $requestContext = $this->getRequestContext($request);

        if ($user instanceof User) {
            $this->securityLogger->info(
                'Admin login successful.',
                array_merge($requestContext, [
                    'user_id' => $user->getId(),
                    'user_identifier' => $user->getUserIdentifier(),
                    'token_class' => get_class($token),
                    'firewall' => $firewallName,
                ])
            );
        }
    }

    public function logAdminLoginFailure(Request $request, AuthenticationException $exception, string $firewallName): void
    {
        $emailAttempted = $request->request->get('_username', '');
        $requestContext = $this->getRequestContext($request);

        $this->securityLogger->warning(
            'Admin login failed.',
            array_merge($requestContext, [
                'email_provided' => $emailAttempted,
                'error_message_key' => $exception->getMessageKey(),
                'error_message_data' => $exception->getMessageData(),
                'firewall' => $firewallName,
            ])
        );
    }

    public function logAdminLoginDeniedForInsufficientRole(Request $request, User $user, string $firewallName): void
    {
        $requestContext = $this->getRequestContext($request);
        $this->securityLogger->warning(
            'Admin login denied - Insufficient role.',
            array_merge($requestContext, [
                'user_id' => $user->getId(),
                'user_identifier' => $user->getUserIdentifier(),
                // Use the enum for the required role
                'required_role' => RoleEnum::EMPLOYEE->value,
                'user_roles' => $user->getRoles(),
                'firewall' => $firewallName,
            ])
        );
    }

    public function logAdminLoginDeniedForDisabledAccount(Request $request, User $user, string $firewallName): void
    {
        $requestContext = $this->getRequestContext($request);
        $this->securityLogger->warning(
            'Admin login denied - Account disabled.',
            array_merge($requestContext, [
                'user_id' => $user->getId(),
                'user_identifier' => $user->getUserIdentifier(),
                'firewall' => $firewallName,
            ])
        );
    }

    public function logAdminLoginCriticalUserInstanceError(Request $request, object $user, TokenInterface $token, string $firewallName): void
    {
        $requestContext = $this->getRequestContext($request);
        $this->securityLogger->critical(
            'User object in token is not an instance of App\\Entity\\User after successful admin authentication.',
            array_merge($requestContext, [
                'user_actual_class' => get_class($user),
                'token_class' => get_class($token),
                'firewall' => $firewallName,
            ])
        );
    }
}