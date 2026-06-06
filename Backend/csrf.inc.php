<?php
// Autor: Sebastian Rieg
// Returns the CSRF token for the current session, generating one if needed.
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Validates the CSRF token from the POST request; responds with 403 on failure.
function csrfPruefen(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Ungültige Anfrage (CSRF-Schutz).');
    }
}
