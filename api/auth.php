<?php
require __DIR__ . '/../config/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {

        // POST: login. Verifica credenziali e restituisce il JWT
        case 'POST':
            $in       = read_body();
            $username = trim($in['username'] ?? '');
            $password = $in['password'] ?? '';

            if ($username === '' || $password === '') {
                respond(['error' => 'Username e password sono obbligatori'], 400);
            }

            // recupero l'utente e il suo hash dal database
            $stmt = $pdo->prepare(
                'SELECT id, username, password_hash FROM utenti WHERE username = ?'
            );
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            // password_verify confronta in modo sicuro la password con l'hash bcrypt
            if (!$user || !password_verify($password, $user['password_hash'])) {
                respond(['error' => 'Credenziali non valide'], 401);
            }

            // credenziali corrette, genero il JWT utilizzando la funzione apposita che unisce l'id dell'utente e l'username
            $token = jwtEncode([
                'user_id'  => (int) $user['id'],
                'username' => $user['username'],
            ]);

            // restituisco il token e lo username al client che lo conserverà in localstorage
            respond([
                'token'    => $token,
                'username' => $user['username'],
            ]); // 200 OK
            break;

        // GET: whoami sapere se si è ancora autenticati
        case 'GET':
            // recupero l'header trasmesso
            $authHeader = getAuthorizationHeader();

            // estraggo il token dall'header recuperato
            if (!preg_match('/^Bearer\s+(.+)$/i', trim($authHeader), $matches)) {
                respond(['authenticated' => false], 401);
            }
            // verifico con jwtDecode la firma del token e recupero il payload
            $payload = jwtDecode($matches[1]);

            if ($payload === null) {
                // Token scaduto, firma non valida o malformato
                respond(['authenticated' => false], 401);
            }

            // token valido, risposta al client
            respond([
                'authenticated' => true,
                'id'            => $payload['user_id'],
                'username'      => $payload['username'],
            ]); // 200 OK
            break;

        // DELETE: logout
        case 'DELETE':
            respond(['message' => 'Logout effettuato. Elimina il token lato client.']);
            break;

        default:
            header('Allow: GET, POST, DELETE');
            respond(['error' => 'Metodo non consentito'], 405);
    }

} catch (PDOException $e) {
    error_log('auth.php: ' . $e->getMessage());
    respond(['error' => 'Errore interno del server'], 500); // 500 INTERNAL SERVER ERROR
}
