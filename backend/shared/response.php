<?php
/**
 * CEMS - Standardized API Response Provider
 * Ensures strict JSON structure conforming to Requirement 21 & 23.
 */

declare(strict_types=1);

// Set CORS headers for API requests
if (!headers_sent()) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
}

// Handle preflight OPTIONS request
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Send a standardized JSON response.
 *
 * @param bool $success
 * @param string $message
 * @param mixed $data
 * @param int $statusCode
 * @param array $errors
 */
function sendJsonResponse(bool $success, string $message, $data = null, int $statusCode = 200, array $errors = []): void {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=UTF-8');
        header('X-Content-Type-Options: nosniff');
    }
    http_response_code($statusCode);

    $response = [
        'success' => $success,
        'message' => $message,
    ];

    if ($success) {
        $response['data'] = $data !== null ? $data : (object)[];
    } else {
        $response['errors'] = $errors;
        if ($data !== null) {
            $response['data'] = $data;
        }
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Convenience helper for success response.
 */
function sendSuccess(string $message, $data = null, int $statusCode = 200): void {
    sendJsonResponse(true, $message, $data, $statusCode);
}

/**
 * Convenience helper for error response.
 */
function sendError(string $message, $errors = [], int $statusCode = 400): void {
    if (is_int($errors)) {
        $statusCode = $errors;
        $errors = [];
    } elseif (!is_array($errors)) {
        $errors = [];
    }
    sendJsonResponse(false, $message, null, $statusCode, $errors);
}

/**
 * Parse JSON request body or POST payload safely.
 *
 * @return array
 */
function getRequestData(): array {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $parsed = json_decode($raw, true);
        return is_array($parsed) ? $parsed : [];
    }
    return $_POST;
}
