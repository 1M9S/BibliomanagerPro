const API_AUTH = 'api/auth.php';
const API_USERS = 'api/users.php';

// mostra la schermata di login
function mostraLogin() {
    document.getElementById('auth-overlay').style.display = 'flex';
    document.getElementById('app').style.display = 'none';
}
// mostra la dashboard principale
function mostraApp(username) {
    document.getElementById('auth-overlay').style.display = 'none';
    document.getElementById('app').style.display = '';
    document.getElementById('utente-corrente').textContent = username || '';
}

// funzione chiamata al caricamento iniziale della pagina per verificare la validità del token JWT presente nel client
async function verificaSessione() {
    // se non c'è nessun token salvato, mostriamo subito il login
    const token = localStorage.getItem(JWT_TOKEN_KEY);
    if (!token) {
        mostraLogin();
        return;
    }

    try {
        // apiFetch leggerà automaticamente il token da localStorage e lo inserirà nell'header Authorization: Bearer <token>
        const data = await apiFetch(API_AUTH);
        mostraApp(data.username);
        caricaAutori();
        caricaLibri();
    } catch (_) {
        // il token è scaduto o non valido, rimozione e login
        localStorage.removeItem(JWT_TOKEN_KEY);
        mostraLogin();
    }
}

// listener del form di login
document.getElementById('form-login').addEventListener('submit', async (e) => {
    e.preventDefault();
    const username = document.getElementById('login-username').value;
    const password = document.getElementById('login-password').value;
    const errBox = document.getElementById('auth-error');
    errBox.textContent = '';

    try {
        const data = await apiFetch(API_AUTH, {
            method: 'POST',
            body: JSON.stringify({ username, password }),
        });

        // salvataggio token
        localStorage.setItem(JWT_TOKEN_KEY, data.token);
        // reset della pagina di login
        document.getElementById('form-login').reset();
        mostraApp(data.username);
        caricaAutori();
        caricaLibri();
    } catch (err) {
        errBox.textContent = err.message;
    }
});


// listener del form di Registrazione
document.getElementById('form-register').addEventListener('submit', async (e) => {
    e.preventDefault();
    const username = document.getElementById('reg-username').value;
    const password = document.getElementById('reg-password').value;
    const errBox = document.getElementById('auth-error');
    errBox.textContent = '';

    try {
        await apiFetch(API_USERS, {
            method: 'POST',
            body: JSON.stringify({ username, password }),
        });
        document.getElementById('form-register').reset();
        mostraToast('Registrazione completata! Ora puoi accedere.');
        mostraFormLogin();
    } catch (err) {
        errBox.textContent = err.message;
    }
});


// funzione per eseguire il Logout
async function logout() {
    try {
        await apiFetch(API_AUTH, { method: 'DELETE' });
    } catch (_) {

    }
    // rimozione del token finchè non avviene una nuova registrazione
    localStorage.removeItem(JWT_TOKEN_KEY);
    mostraLogin();
}


// passaggio tra form di login e form di registrazione
function mostraFormRegister() {
    document.getElementById('form-login').style.display = 'none';
    document.getElementById('form-register').style.display = '';
    document.getElementById('auth-title').textContent = 'Crea un account';
    document.getElementById('auth-error').textContent = '';
}

function mostraFormLogin() {
    document.getElementById('form-register').style.display = 'none';
    document.getElementById('form-login').style.display = '';
    document.getElementById('auth-title').textContent = 'Accedi';
    document.getElementById('auth-error').textContent = '';
}
