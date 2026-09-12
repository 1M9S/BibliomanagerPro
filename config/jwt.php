<?php
// direttiva che abilita il controllo rigoroso dei tipi in PHP evitando conversioni automatiche
declare(strict_types=1);
// chiave segreta utilizzata per firmare e verificare i token
define('JWT_SECRET', 'kx4RjqCH0tb5sCcVOdGmQ8WI+ezer/bOu9Im2oedCcqES/l+naw5PkP0e4myaVYe');
define('JWT_EXPIRY_SECONDS', 3600); // Durata token (1 ora)

// funzione di conversione Base64URL, sostituisce +, /, = che sono fondamentali negli URL e negli header http
function base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

// funzione di decodifica di una stringa base64url 
function base64url_decode(string $data): string
{
    $pad = strlen($data) % 4;
    if ($pad !== 0) {
        $data .= str_repeat('=', 4 - $pad);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

// crea un JWT firmato quando l'utente effettua il login
function jwtEncode(array $payload): string
{
    // creazione dell'header che dichiara il tipo di token e l'algoritmo di firma
    $header = base64url_encode(json_encode([
        'alg' => 'HS256',
        'typ' => 'JWT',
    ], JSON_UNESCAPED_UNICODE));

    // aggiungo al payload le info temporali (timestamp di emissione e di scadenza)
    $payload['iat'] = time();
    $payload['exp'] = time() + JWT_EXPIRY_SECONDS;

    // codifica del payload sempre con base64url
    $encodedPayload = base64url_encode(json_encode($payload, JSON_UNESCAPED_UNICODE));

    // la firma è calcolata sull'unione di header e payload codificati, uso HMAC-SHA256 con la chiave segreta
    $signature = base64url_encode(
        hash_hmac('sha256', "$header.$encodedPayload", JWT_SECRET, true)
    );
    return "$header.$encodedPayload.$signature";
}

// controlla se un JWT ricevuto è valido
function jwtDecode(string $token): ?array
{
    // un JWT valido ha esattamente tre parti separate da punti
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }

    [$headerB64, $payloadB64, $receivedSig] = $parts; // parti estratte 

    // ricalcolo della firma per verificare la corrispondenza con la signature ricevuta
    $expectedSig = base64url_encode(
        hash_hmac('sha256', "$headerB64.$payloadB64", JWT_SECRET, true)
    );

    // confronto delle firme
    if (!hash_equals($expectedSig, $receivedSig)) {
        return null; // firma non valida: token alterato o chiave sbagliata
    }

    // decodifico il payload
    $payload = json_decode(base64url_decode($payloadB64), true);
    if (!is_array($payload)) {
        return null; // payload malformato
    }

    // verifica della scadenza
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return null; // token scaduto
    }
    return $payload;
}

// recupera l'header inviato dal client dipendente dalla configurazione del server
function getAuthorizationHeader(): string
{
    //  primo tentativo: modo standard in PHP per leggere gli header HTTP
    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        return $_SERVER['HTTP_AUTHORIZATION'];
    }

    // secondo tentativo: quando Apache usa mod_rewrite e modifica il nome dell'header
    if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }

    // terzo tentativo : tramite apache_request_headers() Recupera direttamente tutti gli header HTTP ricevuti
    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'authorization') { // cerca authorization per prelevare l'header
                return $value;
            }
        }
    }
    return '';
}
