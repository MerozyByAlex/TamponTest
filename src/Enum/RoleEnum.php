<?php

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Defines the available user roles in the application.
 * Using a string-backed enum ensures that the values stored in the database
 * and used in security configurations are consistent.
 */
enum RoleEnum: string
{
    // ==== ROLES DE BASE / VIRTUELS ====
    /**
     * Base role for all authenticated users (customers).
     */
    case CUSTOMER = 'ROLE_CUSTOMER';

    /**
     * Base role for all backend staff/employees.
     * Used to grant common backend access.
     */
    case EMPLOYEE = 'ROLE_EMPLOYEE';


    // ==== BACKEND (Employee) SPECIFIC ROLES ====

    /**
     * Administrator with full access to all backend functionalities.
     * Inherits permissions from all other backend roles and customer role.
     */
    case ADMIN = 'ROLE_ADMIN';

    /**
     * Technical account for SAGE software integration.
     */
    case SAGE = 'ROLE_SAGE';

    /**
     * User(s) in charge of commercial aspects.
     */
    case SELLER = 'ROLE_SELLER';

    /**
     * User(s) in charge of logistics.
     */
    case LOGISTICIAN = 'ROLE_LOGISTICIAN';

    /**
     * User(s) in charge of GDPR compliance.
     */
    case DATA_AGENT = 'ROLE_DATA_AGENT';

    /**
     * User(s) in charge of after-sales service.
     */
    case AFTERSALES = 'ROLE_AFTERSALES';

    /**
     * Technical user with restricted access to diagnostic or monitoring features.
     */
    case DEV = 'ROLE_DEV';


    // ==== FRONTEND (Customer) SPECIFIC ROLES ====

    /**
     * Customer with access to the "Coutant TTC" pricing category.
     */
    case CUSTOMER_CE = 'ROLE_CUSTOMER_CE';

    /**
     * Professional customer with access to HT (tax-exclusive) pricing.
     */
    case CUSTOMER_PRO = 'ROLE_CUSTOMER_PRO';


    /**
     * Provides a human-readable label for the role.
     * Uses Symfony's Translator component if available, otherwise falls back to default labels.
     * Translation keys should be defined in your translation files (e.g., 'role.ROLE_ADMIN').
     */
    public function getLabel(TranslatorInterface $translator = null): string
    {
        $translationKey = 'role.' . $this->value;

        if ($translator) {
            // Attempt to translate; if the key is not found, it will return the key itself by default.
            // We can check if the translation is different from the key to use the fallback.
            $translatedLabel = $translator->trans($translationKey);
            if ($translatedLabel !== $translationKey) {
                return $translatedLabel;
            }
        }

        // Fallback labels if no translator is provided or if translation key is missing
        return match ($this) {
            // Base
            self::CUSTOMER => 'Customer',
            self::EMPLOYEE => 'Employee',
            // Backend
            self::ADMIN => 'Administrator',
            self::SAGE => 'SAGE System Account',
            self::SELLER => 'Seller',
            self::LOGISTICIAN => 'Logistician',
            self::DATA_AGENT => 'Data Compliance Agent',
            self::AFTERSALES => 'After-Sales Agent',
            self::DEV => 'Developer / Technical Support',
            // Frontend
            self::CUSTOMER_CE => 'CE Customer',
            self::CUSTOMER_PRO => 'Professional Customer',
        };
    }

    /**
     * @return list<string>
     */
    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return list<self>
     */
    public static function getBackendRoles(bool $includeAdmin = true, bool $includeSage = true): array
    {
        $roles = [
            self::EMPLOYEE, // Base for all employees
            self::SELLER,
            self::LOGISTICIAN,
            self::DATA_AGENT,
            self::AFTERSALES,
            self::DEV,
        ];
        if ($includeAdmin) {
            $roles[] = self::ADMIN;
        }
        if ($includeSage) {
            $roles[] = self::SAGE;
        }
        return array_unique($roles); // Ensure uniqueness if ADMIN/SAGE also considered base employees
    }

    /**
     * @return list<self>
     */
    public static function getFrontendCustomerRoles(): array
    {
        return [
            self::CUSTOMER, // Base customer role
            self::CUSTOMER_CE,
            self::CUSTOMER_PRO,
        ];
    }

    public function isBackendEmployeeRole(): bool
    {
        // SAGE might be a system account, not strictly an "employee" login
        // ADMIN is an employee.
        return in_array($this, [
            self::EMPLOYEE,
            self::ADMIN,
            self::SELLER,
            self::LOGISTICIAN,
            self::DATA_AGENT,
            self::AFTERSALES,
            self::DEV,
        ], true);
    }

    public function isFrontendCustomerRole(): bool
    {
        return in_array($this, self::getFrontendCustomerRoles(), true);
    }

    /**
     * Provides a structured representation of the role hierarchy for security.yaml.
     * @return array<string, list<string>>
     */
    public static function getRoleHierarchy(): array
    {
        return [
            // Admin inherits from all employee capabilities, SAGE, and base customer for viewing
            self::ADMIN->value => [
                self::EMPLOYEE->value,
                self::SAGE->value,
                self::SELLER->value,
                self::LOGISTICIAN->value,
                self::DATA_AGENT->value,
                self::AFTERSALES->value,
                self::DEV->value,
                self::CUSTOMER->value,
            ],

            // Specific employee roles inherit from the base EMPLOYEE role
            self::SELLER->value      => [self::EMPLOYEE->value],
            self::LOGISTICIAN->value => [self::EMPLOYEE->value],
            self::DATA_AGENT->value  => [self::EMPLOYEE->value],
            self::AFTERSALES->value  => [self::EMPLOYEE->value],
            self::DEV->value        => [self::EMPLOYEE->value],

            // Specific customer roles inherit from the base CUSTOMER role
            self::CUSTOMER_CE->value  => [self::CUSTOMER->value],
            self::CUSTOMER_PRO->value => [self::CUSTOMER->value],
        ];
    }
}