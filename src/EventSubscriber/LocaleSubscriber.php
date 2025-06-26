<?php
// src/EventSubscriber/LocaleSubscriber.php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleSubscriber implements EventSubscriberInterface
{
    private string $defaultLocale;
    private array $supportedLocales;

    // Constructor now accepts both default locale and the list of supported locales
    // $defaultLocale will be 'fr' from services.yaml for admin context
    // $supportedLocales will be ['en', 'fr', 'de', 'es'] from services.yaml
    public function __construct(string $defaultLocale, array $supportedLocales)
    {
        $this->defaultLocale = $defaultLocale;
        $this->supportedLocales = $supportedLocales;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Do not modify locale for stateless parts like API or if no session is available (e.g., CLI)
        // For API, locale might be handled differently (e.g. Accept-Language header for data, not for UI)
        // For Next.js frontend, locale is handled by Next.js routing and its own i18n
        if (!$request->hasPreviousSession() || !$request->hasSession() || str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $session = $request->getSession();
        $localeToSet = null;

        // 1. Try to get locale from session if it's a supported one
        $sessionLocale = $session->get('_locale');
        if ($sessionLocale && in_array($sessionLocale, $this->supportedLocales, true)) {
            $localeToSet = $sessionLocale;
        }

        // 2. If not in session, try to determine from browser's preferred language (for initial visit)
        if (!$localeToSet) {
            $preferredLanguages = $request->getLanguages();
            foreach ($preferredLanguages as $preferredLanguage) {
                $langCode = substr($preferredLanguage, 0, 2); // e.g., 'fr' from 'fr-FR'
                if (in_array($langCode, $this->supportedLocales, true)) {
                    $localeToSet = $langCode;
                    break;
                }
            }
        }
        
        // 3. If still no locale found or preferred is not supported, use the injected default locale (which is 'fr' for admin)
        if (!$localeToSet) {
            $localeToSet = $this->defaultLocale;
        }
        
        $request->setLocale($localeToSet);
        // Also ensure the session is updated with the determined locale for subsequent requests
        $session->set('_locale', $localeToSet);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // High priority to set locale early, but after routing and session start
            KernelEvents::REQUEST => [['onKernelRequest', 16]],
        ];
    }
}