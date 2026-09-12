<?php
// direttiva che abilita il controllo rigoroso dei tipi in PHP evitando conversioni automatiche
declare(strict_types=1);

// impostazione header affinchè tutte le risposte del server siano restituite in formato json
header('Content-Type: application/json; charset=UTF-8');

// caricamento del modulo JWT contenente le funzioni (jwtEncode, jwtDecode, getAuthorizationHeader)
require __DIR__ . '/jwt.php';

// funzione di utilità che standardizza l'invio delle risposta dell'API, riceve i dati da inoltrare ed il codice di stato HTTP
function respond($data, int $status = 200): void
{
    // impostazione codice di stato HTTP
    http_response_code($status);
    // conversione dei dati in formato json
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    // exit per evitare che il server elabori codice a vuoto dopo aver inviato una risposta al client
    exit; 
}

// funzione di utilità che standardizza la lettura del corpo delle richieste HTTP
function read_body(): array
{
    // lettura del contenuto
    $raw  = file_get_contents('php://input');
    // decodifica da json in array associativo php
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

// funzione di utilità che protegge un endpoint: estrae il JWT dall'header Authorization, lo valida e, 
// se valido, rende il payload disponibile tramite $GLOBALS['auth_payload']. Approccio stateless
function require_auth(): void
{
    // recupera l'header
    $authHeader = getAuthorizationHeader();

    // l'header deve avere il formato: "Authorization: Bearer <token>"
    if (!preg_match('/^Bearer\s+(.+)$/i', trim($authHeader), $matches)) {
        respond(['error' => 'Token di autenticazione mancante'], 401);
    }

    $token   = $matches[1];
    $payload = jwtDecode($token);

    if ($payload === null) {
        // il token è scaduto, la firma non è valida, o è malformato
        respond(['error' => 'Token non valido o scaduto. Effettuare nuovamente il login.'], 401);
    }

    // rendiamo disponibile il payload (user_id, username) agli endpoint.
    $GLOBALS['auth_payload'] = $payload;
}

// carica la connessione al database.
require __DIR__ . '/db.php';
