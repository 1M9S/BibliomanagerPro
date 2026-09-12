// chiave (Costante) usata per salvare e leggere il JWT in localStorage
const JWT_TOKEN_KEY = 'bibliomanager_token';

// converte i caratteri speciali in formato sicuro prima di mostrarli nella pagina.
// serve a evitare che dati inseriti dagli utenti possano eseguire codice HTML o JavaScript per evitare XSS(Cross-Site Scripting)

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// funzione che gestisce le richieste HTTP verso il backend aggiungendo automaticamente il token JWT alla richiesta
async function apiFetch(url, options = {}) {
    // crea una copia delle impostazioni della richiesta (metodo, body, header), così da poter aggiungere informazioni come il token JWT senza modificare l'oggetto originale
    const opts = {
        ...options,
        headers: { ...(options.headers || {}) },
    };

    // se esiste un JWT salvato in localStorage, viene allegato all'header. Il formato standard è: Authorization: Bearer <token>
    const token = localStorage.getItem(JWT_TOKEN_KEY);
    if (token) {
        opts.headers['Authorization'] = `Bearer ${token}`;
    }

    // se la richiesta contiene dati json, comunica al server il formato del contenuto
    if (opts.body && !opts.headers['Content-Type']) {
        opts.headers['Content-Type'] = 'application/json';
    }

    // inoltrazione della richiesta al server
    const res = await fetch(url, opts);

    // lettura della risposta
    let data = null;
    try { data = await res.json(); } catch (_) { }

    // se il server restituisce un errore, genera un'eccezione
    if (!res.ok) {
        const err = new Error((data && data.error) || `Errore ${res.status}`);
        err.status = res.status;
        throw err;
    }
    return data;
}

// gestione centralizzata degli errori delle chiamate API. Se il token è scaduto o non valido viene rimosso
function gestisciErroreApi(err, messaggioDefault = 'Si è verificato un errore') {
    if (err && err.status === 401) {
        localStorage.removeItem(JWT_TOKEN_KEY);
        if (typeof mostraLogin === 'function') mostraLogin();
        return;
    }
    mostraToast((err && err.message) ? err.message : messaggioDefault);
}


// notifiche temporanee (toast)
function mostraToast(messaggio) {
    const toast = document.getElementById('toast');
    toast.textContent = messaggio;
    toast.className = 'toast show';
    setTimeout(() => {
        toast.className = toast.className.replace('show', '');
    }, 3000);
}


// modale di modifica libro
function apriModale(id, titolo, anno, autore_id) {
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-titolo').value = titolo;
    document.getElementById('edit-anno').value = anno;
    document.getElementById('edit-autore-id').value = autore_id;
    document.getElementById('modal-edit').style.display = 'flex';
}
// funzione per chiudere la modale
function chiudiModale() {
    document.getElementById('modal-edit').style.display = 'none';
}

// chiusura della modale cliccando sullo sfondo scuro
window.addEventListener('click', (event) => {
    if (event.target === document.getElementById('modal-edit')) {
        chiudiModale();
    }
});
