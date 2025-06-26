<?php
// src/EventSubscriber/CorrelationIdSubscriber.php

namespace App\EventSubscriber;

use App\Constants\SecurityConstants; // Use our new constants class
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * An event subscriber that ensures every request has a unique correlation ID
 * and attaches request metadata for comprehensive logging.
 */
class CorrelationIdSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 256], // High priority
            KernelEvents::RESPONSE => ['onKernelResponse'],
        ];
    }

    /**
     * Sets the correlation ID and request metadata on the main request.
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // 1. Set Correlation ID
        $correlationId = $request->headers->get(SecurityConstants::CORRELATION_ID_HEADER);
        if (empty($correlationId)) {
            $correlationId = uniqid('req_', true);
        }
        $request->attributes->set(SecurityConstants::CORRELATION_ID_ATTRIBUTE, $correlationId);

        // 2. Set Request Metadata for logging
        $requestMetadata = [
            'host' => $request->getHost(),
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'locale' => $request->getLocale(),
        ];
        $request->attributes->set(SecurityConstants::REQUEST_METADATA_ATTRIBUTE, $requestMetadata);
    }

    /**
     * Adds the correlation ID to the response headers.
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        if ($request->attributes->has(SecurityConstants::CORRELATION_ID_ATTRIBUTE)) {
            $response->headers->set(
                SecurityConstants::CORRELATION_ID_HEADER,
                $request->attributes->get(SecurityConstants::CORRELATION_ID_ATTRIBUTE)
            );
        }
    }
}