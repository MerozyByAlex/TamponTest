<?php
// src/EventSubscriber/RedirectToLocalizedRouteSubscriber.php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

class RedirectToLocalizedRouteSubscriber implements EventSubscriberInterface
{
    private RouterInterface $router;
    private string $defaultLocale;
    private array $supportedLocales;

    public function __construct(RouterInterface $router, string $defaultLocale, array $supportedLocales)
    {
        $this->router = $router;
        $this->defaultLocale = $defaultLocale;
        $this->supportedLocales = $supportedLocales;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $path = $request->getPathInfo();

        // Ne redirige pas si :
        // - La route contient déjà une locale
        // - C’est une API ou un chemin Symfony interne
        // - C’est une requête Ajax ou favicon
        if (
            $request->attributes->has('_locale') ||
            str_starts_with($path, '/api') ||
            str_starts_with($path, '/_profiler') ||
            str_starts_with($path, '/_wdt') ||
            $request->isXmlHttpRequest() ||
            $path === '/favicon.ico'
        ) {
            return;
        }

        // Ne redirige pas si l’URL commence déjà par une locale supportée
        if (preg_match('#^/(' . implode('|', $this->supportedLocales) . ')(/|$)#', $path)) {
            return;
        }

        // Détection de la locale à appliquer
        $session = $request->hasSession() ? $request->getSession() : null;
        $preferredLocale = $session?->get('_locale');

        if (!$preferredLocale || !in_array($preferredLocale, $this->supportedLocales, true)) {
            foreach ($request->getLanguages() as $lang) {
                $lang = substr($lang, 0, 2);
                if (in_array($lang, $this->supportedLocales, true)) {
                    $preferredLocale = $lang;
                    break;
                }
            }
        }

        $locale = $preferredLocale ?: $this->defaultLocale;
        $localizedPath = '/' . $locale . $path;

        // Redirection vers l'URL localisée
        $event->setResponse(new RedirectResponse($localizedPath));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 15]],
        ];
    }
}