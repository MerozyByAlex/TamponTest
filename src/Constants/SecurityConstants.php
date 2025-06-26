<?php
// src/Constants/SecurityConstants.php

namespace App\Constants;

/**
 * Defines constants related to application security.
 * Centralizing these values prevents typos and eases maintenance.
 */
final class SecurityConstants
{
    /**
     * The name of the request attribute used to store the correlation ID.
     * The leading underscore is a convention for internal/framework attributes.
     */
    public const CORRELATION_ID_ATTRIBUTE = '_correlation_id';

    /**
     * The name of the HTTP header for the correlation ID.
     */
    public const CORRELATION_ID_HEADER = 'X-Correlation-ID';

    /**
     * The name of the request attribute used to store extra request metadata for logging.
     */
    public const REQUEST_METADATA_ATTRIBUTE = '_request_metadata';
}