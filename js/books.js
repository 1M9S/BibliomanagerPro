const API_BOOKS = 'api/books.php';

// cache dei libri attualmente mostrati (id -> oggetto libro)
let libriCorrenti = {};

/*
  Carica i libri e li mostra come card.
  Se è selezionato un autore nel filtro, aggiunge ?author_id=N all'URL
*/
async function caricaLibri() {
    const container = document.getElementById('lista-libri');
    const autoreId = document.getElementById('filtro-autore').value;

    let url = API_BOOKS;
    if (autoreId) url += `?author_id=${encodeURIComponent(autoreId)}`;

    container.innerHTML = '<p class="loading">Caricamento...</p>';

    try {
        const libri = await apiFetch(url);

        container.innerHTML = '';

        if (!libri.length) {
            container.innerHTML = '<p>Nessun libro trovato.</p>';
            return;
        }

        libri.forEach((libro) => {
            libriCorrenti[libro.id] = libro;

            const card = document.createElement('div');
            card.className = 'card-libro';
            card.innerHTML = `
                <div class="card-header"><span class="badge">${escapeHtml(libro.anno)}</span></div>
                <h3>${escapeHtml(libro.titolo)}</h3>
                <p class="autore">${escapeHtml(libro.nome)} ${escapeHtml(libro.cognome)}</p>
                <div class="card-actions">
                    <button class="btn-secondary" data-action="edit"   data-id="${libro.id}">Modifica</button>
                    <button class="btn-delete"    data-action="delete" data-id="${libro.id}">Elimina</button>
                </div>`;
            container.appendChild(card);
        });
    } catch (err) {
        gestisciErroreApi(err, 'Errore nel caricamento dei libri');
        if (err.status !== 401) {
            container.innerHTML = '<p>Errore nel caricamento dei libri.</p>';
        }
    }
}


// elimina un libro
async function eliminaLibro(id) {
    if (!confirm('Eliminare definitivamente questo libro?')) return;
    try {
        await apiFetch(`${API_BOOKS}?id=${encodeURIComponent(id)}`, {
            method: 'DELETE',
        });
        caricaLibri();
        mostraToast('Libro eliminato.');
    } catch (err) {
        gestisciErroreApi(err, "Errore durante l'eliminazione del libro");
    }
}

// modifica di un libro esistente
document.getElementById('form-edit').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = document.getElementById('edit-id').value;
    const titolo = document.getElementById('edit-titolo').value;
    const anno = document.getElementById('edit-anno').value;
    const autore_id = document.getElementById('edit-autore-id').value;

    try {
        await apiFetch(`${API_BOOKS}?id=${encodeURIComponent(id)}`, {
            method: 'PUT',
            body: JSON.stringify({ titolo, anno, autore_id }),
        });
        chiudiModale();
        caricaLibri();
        mostraToast('Modifica salvata!');
    } catch (err) {
        gestisciErroreApi(err, 'Errore durante la modifica del libro');
    }
});

// un solo listener gestisce Modifica ed Elimina di tutte le card
document.getElementById('lista-libri').addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-action]');
    if (!btn) return;
    const id = btn.dataset.id;
    if (btn.dataset.action === 'edit') {
        const libro = libriCorrenti[id];
        if (libro) apriModale(libro.id, libro.titolo, libro.anno, libro.autore_id);
    } else if (btn.dataset.action === 'delete') {
        eliminaLibro(id);
    }
});

// aggiunta di un nuovo libro (POST)
document.getElementById('form-libro').addEventListener('submit', async (e) => {
    e.preventDefault();
    const titolo = document.getElementById('titolo').value;
    const anno = document.getElementById('anno').value;
    const autore_id = document.getElementById('select-autori').value;

    try {
        await apiFetch(API_BOOKS, {
            method: 'POST',
            body: JSON.stringify({ titolo, anno, autore_id }),
        });
        document.getElementById('form-libro').reset();
        caricaLibri();
        mostraToast('Libro salvato!');
    } catch (err) {
        gestisciErroreApi(err, "Errore durante il salvataggio del libro");
    }
});


