<?php
require __DIR__ . '/../config/bootstrap.php';

require_auth(); // verifica il JWT nell'header Authorization

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {

        // GET: lettura libri, con filtro opzionale per autore        
        case 'GET':
            $author_id = filter_input(INPUT_GET, 'author_id', FILTER_VALIDATE_INT);

            $sql = "SELECT libri.id, libri.titolo, libri.anno,
                           autori.nome, autori.cognome, libri.autore_id
                    FROM libri
                    JOIN autori ON libri.autore_id = autori.id";

            if ($author_id) {
                $sql .= " WHERE libri.autore_id = :aid";
            }
            $sql .= " ORDER BY libri.titolo ASC";

            $stmt = $pdo->prepare($sql);
            if ($author_id) {
                $stmt->bindValue(':aid', $author_id, PDO::PARAM_INT);
            }
            $stmt->execute();
            respond($stmt->fetchAll());
            break;

        // POST: creazione di un nuovo libro
        case 'POST':
            $in        = read_body();
            $titolo    = trim($in['titolo'] ?? '');
            $anno      = filter_var($in['anno'] ?? null, FILTER_VALIDATE_INT);
            $autore_id = filter_var($in['autore_id'] ?? null, FILTER_VALIDATE_INT);

            if ($titolo === '' || $anno === false || !$autore_id) {
                respond(['error' => 'Dati non validi: titolo, anno e autore sono obbligatori'], 400); // 400 BAD REQUEST
            }

            // verifico se esiste già un libro con lo stesso titolo e autore
            $checkStmt = $pdo->prepare("SELECT id FROM libri WHERE titolo = ? AND autore_id = ?");
            $checkStmt->execute([$titolo, $autore_id]);
            if ($checkStmt->fetchColumn()) {
                respond(['error' => 'Questo libro è già presente nel database'], 409); // 409 CONFLICT
            }

            $stmt = $pdo->prepare(
                "INSERT INTO libri (titolo, anno, autore_id) VALUES (?, ?, ?)"
            );
            $stmt->execute([$titolo, $anno, $autore_id]);

            respond([
                'id'      => (int) $pdo->lastInsertId(),
                'message' => 'Libro aggiunto',
            ], 201); // 201 CREATED
            break;


        // PUT: aggiornamento di un libro
        case 'PUT':
            // L'id della risorsa è nell'URL
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                respond(['error' => 'ID non valido o mancante nell\'URL (?id=N)'], 400); // 400 BAD REQUEST
            }
            // lettura del body per i campi di aggiornamento
            $in        = read_body();
            $titolo    = trim($in['titolo'] ?? '');
            $anno      = filter_var($in['anno'] ?? null, FILTER_VALIDATE_INT);
            $autore_id = filter_var($in['autore_id'] ?? null, FILTER_VALIDATE_INT);

            if ($titolo === '' || $anno === false || !$autore_id) {
                respond(['error' => 'Dati non validi'], 400); // 400 BAD REQUEST
            }

            // verifica se le nuove modifiche coincidano con un altro libro già esistente nel database
            $checkStmt = $pdo->prepare("SELECT id FROM libri WHERE titolo = ? AND autore_id = ? AND id != ?");
            $checkStmt->execute([$titolo, $autore_id, $id]);
            if ($checkStmt->fetchColumn()) {
                respond(['error' => 'Un altro libro con questo titolo e autore è già presente'], 409); // 409 CONFLICT
            }

            $stmt = $pdo->prepare(
                "UPDATE libri SET titolo = ?, anno = ?, autore_id = ? WHERE id = ?"
            );
            $stmt->execute([$titolo, $anno, $autore_id, $id]);

            if ($stmt->rowCount() === 0) {
                $check = $pdo->prepare("SELECT 1 FROM libri WHERE id = ?");
                $check->execute([$id]);
                if (!$check->fetchColumn()) {
                    respond(['error' => 'Libro non trovato'], 404); // 404 NOT FOUND
                }
            }

            respond(['message' => 'Libro aggiornato']);
            break;

        // DELETE: eliminazione, l'id arriva dalla query string (?id=N)
        case 'DELETE':
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                respond(['error' => 'ID non valido o mancante nell\'URL (?id=N)'], 400); // 400 BAD REQUEST
            }

            $stmt = $pdo->prepare("DELETE FROM libri WHERE id = ?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() === 0) {
                respond(['error' => 'Libro non trovato'], 404); // 404 NOT FOUND
            }

            respond(['message' => 'Libro eliminato']); // 200 OK
            break;

        default:
            header('Allow: GET, POST, PUT, DELETE');
            respond(['error' => 'Metodo non consentito'], 405); // 405 METHOD NOT ALLOWED
    }
} catch (PDOException $e) {
    error_log('books.php: ' . $e->getMessage());
    respond(['error' => 'Errore interno del server'], 500); // 500 INTERNAL SERVER ERROR
}
