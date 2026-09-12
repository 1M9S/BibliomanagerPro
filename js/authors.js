const API_AUTHORS = 'api/authors.php';
// funzione che recupera dal backend l'elenco degli autori e aggiorna gli elementi dell'interfaccia che utilizzano tali dati
async function caricaAutori() {
    // recupera la select utilizzata per filtrare i libri in base all'autore
    const filtro = document.getElementById('filtro-autore');
    // recupera la select del form di inserimento libro, dove viene scelto l'autore
    const selectInsert = document.getElementById('select-autori');
    // recupera la select presente nella finestra di modifica libro usata per cambiare autore associato a un libro
    const selectEdit = document.getElementById('edit-autore-id');
    // recupera il contenitore della sezione gestione autori
    const listaGestione = document.getElementById('lista-autori-gestione');

    try {
        // richiesta HTTP GET per recuperare gli autori
        const autori = await apiFetch(API_AUTHORS);
        // memorizza il valore di filtro selezionato dall'utente
        const valFiltro = filtro.value;

        // svuota le select e la lista degli autori prima di inserire i nuovi dati ricevuti dal backend
        // In questo modo si evitano duplicati e l'interfaccia viene aggiornata correttamente
        // ogni volta che viene ricaricato l'elenco degli autori
        filtro.innerHTML = '<option value="">-- Tutti i Libri --</option>';
        selectInsert.innerHTML = '<option value="">Seleziona Autore...</option>';
        selectEdit.innerHTML = '';
        listaGestione.innerHTML = '';

        // ciclo per ogni autore ricevuto e inserimento nelle select e nella lista precedentemente svuotati
        autori.forEach((autore) => {
            const nomeCompleto = escapeHtml(`${autore.cognome} ${autore.nome}`);
            const id = autore.id;

            filtro.innerHTML += `<option value="${id}">${nomeCompleto}</option>`;
            selectInsert.innerHTML += `<option value="${id}">${nomeCompleto}</option>`;
            selectEdit.innerHTML += `<option value="${id}">${nomeCompleto}</option>`;

            listaGestione.innerHTML += `
                <li>
                    <span>${nomeCompleto}</span>
                    <button class="btn-xs-delete" data-id="${id}" title="Elimina">Elimina</button>
                </li>`;
        });

        // ripristino del filtro
        filtro.value = valFiltro;

    } catch (err) {
        gestisciErroreApi(err, 'Errore nel caricamento degli autori');
    }
}

// eliminazione di un autore.
async function eliminaAutore(id) {
    if (!confirm("Eliminando l'autore verranno eliminati anche i suoi libri. Continuare?")) {
        return;
    }
    try {
        await apiFetch(`${API_AUTHORS}?id=${encodeURIComponent(id)}`, {
            method: 'DELETE',
        });
        mostraToast('Autore eliminato');
        caricaAutori();
        caricaLibri();
    } catch (err) {
        gestisciErroreApi(err, "Errore durante l'eliminazione dell'autore");
    }
}

// event delegation per i pulsanti elimina della lista autori
document.getElementById('lista-autori-gestione').addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-id]'); // controlla che il pulsante abbia l'attributo data-id
    if (btn) eliminaAutore(btn.dataset.id); // eliminazione dell'autore con l'id specifico
});

// aggiunta di un nuovo autore
document.getElementById('form-autore').addEventListener('submit', async (e) => {
    e.preventDefault();
    const nome = document.getElementById('new-nome').value;
    const cognome = document.getElementById('new-cognome').value;

    try {
        await apiFetch(API_AUTHORS, {
            method: 'POST',
            body: JSON.stringify({ nome, cognome }),
        });
        document.getElementById('form-autore').reset();
        caricaAutori();
        mostraToast('Autore aggiunto!');
    } catch (err) {
        gestisciErroreApi(err, "Errore durante l'aggiunta dell'autore");
    }
});
