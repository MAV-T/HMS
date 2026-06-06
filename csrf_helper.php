<?php
/**
 * csrf_helper.php — Centralizzazione protezione CSRF
 *
 * Pattern: Synchronizer Token Pattern
 * CWE:     CWE-352 (Cross-Site Request Forgery)
 * OWASP:   A01:2021 – Broken Access Control
 * OpenCRE: https://www.opencre.org/cre/340-310
 *
 * Utilizzo:
 *   - In ogni form HTML:
 *       <?= csrf_token_field() ?>
 *   - All'inizio di ogni handler POST (prima della business logic):
 *       verify_csrf_token();
 */

/**
 * Genera (o recupera dalla sessione) il token CSRF.
 * Usa random_bytes(32) per entropia crittograficamente sicura.
 *
 * @return string token esadecimale a 64 caratteri
 */
function generate_csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Restituisce il campo hidden HTML già formattato da inserire nel form.
 *
 * @return string  <input type="hidden" name="csrf_token" value="...">
 */
function csrf_token_field(): string {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Verifica il token CSRF inviato con la richiesta POST.
 * Usa hash_equals() per confronto a tempo costante (prevenzione timing attack).
 * Termina l'esecuzione con HTTP 403 in caso di mismatch.
 *
 * @return void
 */
function verify_csrf_token(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (
        empty($_SESSION['csrf_token']) ||
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        http_response_code(403);
        die('CSRF token non valido. Operazione rifiutata.');
    }
}
