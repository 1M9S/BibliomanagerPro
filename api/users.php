<?php
require __DIR__ . '/../config/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    // questa richiesta accetta solo POST.
    if ($method !== 'POST') {
        header('Allow: POST');
        respond(['error' => 'Metodo non consentito'], 405); // 405 METHOD NOT ALLOWED
    }

    $in       = read_body();
    $username = trim($in['username'] ?? '');
    $password = $in['password'] ?? '';

    // validazione minima della robustezza delle credenziali lato server
    if (strlen($username) < 3 || strlen($password) < 6) {
        respond(['error' => 'Username minimo 3 caratteri, password minimo 6'], 400);
    }

    // password_hash genera un hash bcrypt con salto casuale incorporato per ogni password inserita
    $hash = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO utenti (username, password_hash) VALUES (?, ?)"
        );
        $stmt->execute([$username, $hash]);
    } catch (PDOException $e) {
        // SQLSTATE 23000 = violazione di un vincolo di integrità.
        // qui significa che lo username (UNIQUE) è già presente.
        if ($e->getCode() === '23000') {
            respond(['error' => 'Username già in uso'], 409); // 409 CONFLICT
        }
        throw $e; // altri errori vengono gestiti dal catch esterno
    }

    respond(['message' => 'Registrazione completata'], 201);   // 201 CREATED

} catch (PDOException $e) {
    error_log('users.php: ' . $e->getMessage());
    respond(['error' => 'Errore interno del server'], 500); // 500 INTERNAL SERVER ERROR
}
