<?php
// parametri di connessione
$host    = '127.0.0.1';        
$db      = 'mybooklist_db';    
$user    = 'root';           
$pass    = '';                
$charset = 'utf8mb4';         

// DSN (Data Source Name): stringa che descrive la sorgente dati
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// opzioni di PDO
$options = [
    // gli errori SQL diventano eccezioni (PDOException), così le possiamo intercettare evitando fallimenti
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    // i risultati del db vengono restituiti come array associativi (chiave = colonna)
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    // disattiva l’emulazione dei prepared statement in PHP e usa quelli nativi di MySQL
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    // creazione della connessione PDO
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    error_log('Connessione DB fallita: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['error' => 'Errore di connessione al database']);
    exit;
}
