<?php
// Gibt den CSRF-Token der aktuellen Session zurück (erstellt ihn bei Bedarf)
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Prüft den CSRF-Token aus dem POST-Request; bricht mit 403 ab bei Fehler
function csrfPruefen(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Ungültige Anfrage (CSRF-Schutz).');
    }
}
