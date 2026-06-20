<?php

/**
 * Common guard for all API endpoints.
 * - boots the app
 * - requires login
 * - requires POST for write actions (caller may relax for GET reads)
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/User.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    jsonResponse(false, 'Not permitted. Please log in again.');
}

/**
 * Ensure the request method matches. Otherwise return error JSON.
 */
function requireMethod(string $method): void
{
    if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {
        jsonResponse(false, 'Invalid request method.');
    }
}

/**
 * Ensure current user is admin or manager (most privileged endpoints).
 */
function requireAdminApi(): void
{
    if (!isAdminOrManager()) {
        jsonResponse(false, 'You are not permitted to do this.');
    }
}

/**
 * Ensure current user is strictly admin (user management, settings).
 */
function requireStrictAdminApi(): void
{
    if (!User::isAdmin()) {
        jsonResponse(false, 'Only an admin is permitted to do this.');
    }
}
