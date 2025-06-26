<?php

namespace App\Service\Logger;

/**
 * Defines a contract for system-level logging services.
 * This allows for decoupling the CustomerManager from a specific logger implementation.
 */
interface SystemLoggerInterface
{
    /**
     * Logs an informational message.
     *
     * @param string $message The message to log.
     * @param array<string, mixed> $context The log context.
     */
    public function info(string $message, array $context = []): void;

    /**
     * Logs a warning message.
     *
     * @param string $message The message to log.
     * @param array<string, mixed> $context The log context.
     */
    public function warning(string $message, array $context = []): void;
    
    /**
     * Logs an error message.
     *
     * @param string $message The message to log.
     * @param array<string, mixed> $context The log context.
     */
    public function error(string $message, array $context = []): void;

    // You can add other logging levels like debug, critical, etc., as needed.
}