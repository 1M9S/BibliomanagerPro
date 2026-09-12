# BiblioManager Pro — Biblioteca Digitale

Single Web Application per la gestione di una biblioteca (autori e libri), con
autenticazione utenti. Frontend HTML/CSS/JavaScript, backend PHP, database MySQL,
comunicazione tramite **API REST** in JSON.

---

## 1. Requisiti

- PHP 7.4 o superiore (consigliato PHP 8+)
- MySQL / MariaDB
- Un server web con PHP (es. **XAMPP**, **MAMP**, o `php -S`)

## 2. Installazione

1. Copia la cartella `Progetto_web` nella root del server web
   (es. `htdocs/` per XAMPP).
2. Crea il database importando `database.sql`:
   - da **phpMyAdmin** → scheda *Importa* → seleziona `database.sql`, oppure
   - da terminale: `mysql -u root -p < database.sql`
3. Se necessario, modifica le credenziali del DB in `config/db.php`.
4. Apri nel browser: `http://localhost/Progetto_web/`

## 3. Credenziali di default

| Username | Password   |
|----------|------------|
| `admin`  | `admin123` |

È possibile anche registrare nuovi account dalla schermata di login
(link "Registrati").

---

## 4. Struttura del progetto

```
Progetto_web/
├── index.html          Pagina unica (SPA): login + gestione biblioteca
├── database.sql        Schema MySQL + dati di esempio
├── api/                Endpoint REST (backend)
│   ├── auth.php        Login / Logout / Stato sessione
│   ├── users.php       Registrazione utenti
│   ├── authors.php     CRUD autori
│   └── books.php       CRUD libri
├── config/
│   ├── db.php          Connessione PDO al database
│   └── bootstrap.php   Sessione, header JSON, funzioni di utilità, auth
├── css/
│   └── style.css       Fogli di stile
├── js/
│   ├── utils.js        escapeHtml, wrapper apiFetch, toast, modale
│   ├── auth.js         Login / registrazione / logout (lato client)
│   ├── authors.js      Gestione autori (lato client)
│   └── books.js        Gestione libri (lato client)
```

---

## 5. API REST

Tutte le risposte sono in JSON. Gli endpoint di autori e libri richiedono una sessione valida (login).

| Metodo | Endpoint                         | Descrizione                       | Stati HTTP            |
|--------|----------------------------------|-----------------------------------|-----------------------|
| POST   | `/api/auth.php`                  | Login                             | 200, 400, 401         |
| GET    | `/api/auth.php`                  | Stato sessione (utente corrente)  | 200, 401              |
| DELETE | `/api/auth.php`                  | Logout                            | 200                   |
| POST   | `/api/users.php`                 | Registrazione                     | 201, 400, 409         |
| GET    | `/api/authors.php`               | Elenco autori                     | 200, 401              |
| POST   | `/api/authors.php`               | Crea autore                       | 201, 400, 401         |
| DELETE | `/api/authors.php`               | Elimina autore (+ libri, cascata) | 200, 400, 401, 404    |
| GET    | `/api/books.php[?author_id=N]`   | Elenco libri (filtrabile)         | 200, 401              |
| POST   | `/api/books.php`                 | Crea libro                        | 201, 400, 401         |
| PUT    | `/api/books.php`                 | Aggiorna libro                    | 200, 400, 401, 404    |
| DELETE | `/api/books.php`                 | Elimina libro                     | 200, 400, 401, 404    |

Metodi non supportati su una risorsa restituiscono **405 Method Not Allowed**;
Errori imprevisti del server restituiscono **500 INTERNAL SERVER ERROR**.

---

## 6. Sicurezza implementata

- **Password**: salvate solo come hash **bcrypt** (`password_hash` / `password_verify`); la password in chiaro non è mai memorizzata.
- **Sessioni**: cookie di sessione PHP; `session_regenerate_id()` dopo il login contro la *session fixation*.
- **SQL injection**: tutte le query usano *prepared statement* (PDO).
- **XSS**: i dati dell'utente passano per `escapeHtml()` prima di finire nel DOM.
- **Endpoint protetti**: autori e libri richiedono autenticazione (`401` se assente).
- **Information disclosure**: gli errori interni non espongono dettagli al client.
