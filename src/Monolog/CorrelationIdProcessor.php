<?php
// src/Monolog/CorrelationIdProcessor.php

namespace App\Monolog;

use App\Constants\SecurityConstants; // Use our new constants class
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * A Monolog processor that adds a correlation ID and request metadata to all log records.
 */
class CorrelationIdProcessor implements ProcessorInterface
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $request = $this->requestStack->getMainRequest();
        if (null === $request) {
            return $record;
        }

        // 1. Add Correlation ID
        if ($correlationId = $request->attributes->get(SecurityConstants::CORRELATION_ID_ATTRIBUTE)) {
            $record->extra['correlation_id'] = $correlationId;
        }

        // 2. Add Request Metadata
        if ($requestMetadata = $request->attributes->get(SecurityConstants::REQUEST_METADATA_ATTRIBUTE)) {
            // Merge metadata into the 'extra' field.
            // Using a 'request' key scopes this information nicely in the final log.
            $record->extra['request'] = $requestMetadata;
        }

        return $record;
    }
}