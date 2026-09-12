<?php
require __DIR__ . '/../config/bootstrap.php';

require_auth(); // verifica il JWT nell'header Authorization

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        // GET: lettura di tutti gli autori
        case 'GET':
            $stmt = $pdo->query(
                "SELECT id, nome, cognome FROM autori ORDER BY cognome ASC, nome ASC"
            );
            respond($stmt->fetchAll()); 
            break;

        // POST: creazione di un nuovo autore
        case 'POST':
            $in      = read_body();
            $nome    = trim($in['nome'] ?? '');
            $cognome = trim($in['cognome'] ?? '');

            if ($nome === '' || $cognome === '') {
                respond(['error' => 'Nome e cognome sono obbligatori'], 400); // 400 BAD REQUEST
            }

            // verifico se esiste già un autore con lo stesso nome e cognome
            $checkStmt = $pdo->prepare("SELECT id FROM autori WHERE nome = ? AND cognome = ?");
            $checkStmt->execute([$nome, $cognome]);
            if ($checkStmt->fetchColumn()) {
                respond(['error' => 'Questo autore è già presente nel database'], 409); // 409 CONFLICT
            }

            $stmt = $pdo->prepare("INSERT INTO autori (nome, cognome) VALUES (?, ?)");
            $stmt->execute([$nome, $cognome]);

            respond([
                'id'      => (int) $pdo->lastInsertId(),
                'message' => 'Autore aggiunto',
            ], 201); // 201 CREATED
            break;
            
        // DELETE: eliminazione — l'id arriva dalla query string (?id=N)
        case 'DELETE':
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                respond(['error' => 'ID non valido o mancante nell\'URL (?id=N)'], 400);
            }
            $stmt = $pdo->prepare("DELETE FROM autori WHERE id = ?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() === 0) {
                respond(['error' => 'Autore non trovato'], 404); // 404 NOT FOUND 
            }

            respond(['message' => 'Autore eliminato']); // 200 OK
            break;

        default:
            header('Allow: GET, POST, DELETE');
            respond(['error' => 'Metodo non consentito'], 405); // 405 METHOD NOT ALLOWED
    }

} catch (PDOException $e) {
    error_log('authors.php: ' . $e->getMessage());
    respond(['error' => 'Errore interno del server'], 500); // 500 INTERNAL SERVER ERROR
}
