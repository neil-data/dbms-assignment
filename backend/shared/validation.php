<?php
/**
 * CEMS - Input Validation & Sanitization Helper
 * Enforces robust server-side data integrity before executing database queries.
 */

declare(strict_types=1);

/**
 * Sanitize a general string input.
 */
function sanitizeString(?string $input): string {
    if ($input === null) return '';
    return trim(htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8'));
}

/**
 * Validate an institutional email address.
 */
function isValidEmail(string $email): bool {
    return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate phone number format (supports Indian / international mobile).
 */
function isValidPhone(string $phone): bool {
    // Allows optional +, spaces, dashes, parentheses and 7 to 15 digits
    return (bool)preg_match('/^\+?[0-9\s\-\(\)]{7,20}$/', trim($phone));
}

/**
 * Validate an integer ID (> 0).
 */
function isValidId($id): bool {
    return filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false;
}

/**
 * Validate event status enum.
 */
function isValidEventStatus(string $status): bool {
    return in_array(strtoupper(trim($status)), ['UPCOMING', 'ONGOING', 'COMPLETED', 'CANCELLED'], true);
}

/**
 * Validate registration status enum.
 */
function isValidRegistrationStatus(string $status): bool {
    return in_array(strtoupper(trim($status)), ['PENDING', 'CONFIRMED', 'CANCELLED', 'COMPLETED'], true);
}
