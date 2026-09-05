document.addEventListener('DOMContentLoaded', () => {
    const app = document.getElementById('notasAdminApp');
    if (!app) return;

    const endpoint = app.dataset.endpoint || '';
    const board = document.getElementById('notasAdminBoard');
    const statusEl = document.getElementById('notasAdminStatus');
    const modal = document.getElementById('notasAdminModal');
    const modalTitle = document.getElementById('notasAdminModalTitle');
    const cardForm = document.getElementById('notasAdminCardForm');
    const searchInput = document.getElementById('notasAdminSearch');
    const deleteCardModalButton = document.querySelector('[data-role="delete-card-modal"]');
    let state = { listas: [] };
    let searchText = '';
    let refreshInProgress = false;

    const setStatus = (message) => {
        if (statusEl) statusEl.textContent = message || '';
    };

    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const normalizeText = (value) => String(value || '').trim().toLowerCase();

    const filterLists = (lists) => {
        if (!searchText) {
            return lists;
        }

        return lists.filter((list) => {
            const listTitle = normalizeText(list.titulo);
            const cards = Array.isArray(list.tarjetas) ? list.tarjetas : [];
            return listTitle.includes(searchText) || cards.some((card) => normalizeText(card.titulo).includes(searchText));
        });
    };

    const postAction = async (action, fields = {}) => {
        const formData = new FormData();
        formData.append('action', action);
        Object.entries(fields).forEach(([key, value]) => {
            formData.append(key, String(value ?? ''));
        });

        const response = await fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        });
        const result = await response.json();
        if (!result.success) {
            throw new Error(result.message || 'No fue posible guardar la nota.');
        }
        state = result.data || { listas: [] };
        render();
    };

    const isModalOpen = () => modal && !modal.classList.contains('notas-hidden');

    const isEditingBoard = () => {
        const active = document.activeElement;
        return Boolean(
            isModalOpen()
            || board?.querySelector('[data-role="list-form"]')
            || active?.closest?.('[data-role="list-title"]')
        );
    };

    const fetchBoard = async ({ silent = false } = {}) => {
        if (!silent) {
            setStatus('Cargando notas...');
        }
        const response = await fetch(endpoint, { credentials: 'same-origin' });
        const result = await response.json();
        if (!result.success) {
            throw new Error(result.message || 'No fue posible cargar las notas.');
        }
        state = result.data || { listas: [] };
        if (!silent) {
            setStatus('');
        }
        if (!silent || !isEditingBoard()) {
            render();
        }
    };

    const refreshBoardSilently = async () => {
        if (refreshInProgress || document.hidden) {
            return;
        }

        refreshInProgress = true;
        try {
            await fetchBoard({ silent: true });
        } catch (error) {
            console.warn(error);
        } finally {
            refreshInProgress = false;
        }
    };

    const renderCard = (card) => `
        <article class="notas-card ${card.completada ? 'is-complete' : ''}" data-card-id="${card.id}">
            <input
                class="notas-check"
                type="checkbox"
                data-role="toggle-card"
                data-card-id="${card.id}"
                ${card.completada ? 'checked' : ''}
                aria-label="Marcar tarjeta"
            >
            <div class="notas-card-main">
                <p class="notas-card-title">${escapeHtml(card.titulo)}</p>
                ${String(card.descripcion || '').trim() ? `<p class="notas-card-desc">${escapeHtml(card.descripcion)}</p>` : ''}
            </div>
            <button
                type="button"
                class="notas-icon-btn"
                data-role="edit-card"
                data-card-id="${card.id}"
                title="Editar tarjeta"
                aria-label="Editar tarjeta"
            >...</button>
        </article>
    `;

    const renderList = (list) => {
        const cards = Array.isArray(list.tarjetas) ? list.tarjetas : [];
        return `
            <section class="notas-list" data-list-id="${list.id}">
                <div class="notas-list-head">
                    <input
                        class="notas-list-title"
                        type="text"
                        value="${escapeHtml(list.titulo)}"
                        data-role="list-title"
                        data-list-id="${list.id}"
                        maxlength="160"
                        aria-label="Titulo de lista"
                    >
                    <span class="notas-count">${cards.length}</span>
                    <button type="button" class="notas-icon-btn notas-delete-list-btn" data-role="delete-list" data-list-id="${list.id}" title="Eliminar lista" aria-label="Eliminar lista">&#128465;</button>
                </div>
                <div class="notas-card-list">
                    ${cards.length ? cards.map(renderCard).join('') : '<div class="notas-empty">Sin tarjetas.</div>'}
                </div>
                <div class="notas-list-actions">
                    <button type="button" class="notas-btn ghost notas-add-card" data-role="add-card" data-list-id="${list.id}">+ Anade una tarjeta</button>
                </div>
            </section>
        `;
    };

    const renderAddList = () => `
        <button type="button" class="notas-add-list" data-role="show-list-form">+ Anade otra lista</button>
    `;

    const renderListForm = () => `
        <form class="notas-list-form" data-role="list-form">
            <input type="text" name="titulo" maxlength="160" placeholder="Titulo de la lista" required>
            <div class="notas-list-form-actions">
                <button type="button" class="notas-btn ghost" data-role="cancel-list-form">Cancelar</button>
                <button type="submit" class="notas-btn primary">Anadir lista</button>
            </div>
        </form>
    `;

    const render = () => {
        if (!board) return;
        const lists = filterLists(Array.isArray(state.listas) ? state.listas : []);
        board.innerHTML = `${lists.map(renderList).join('')}${renderAddList()}`;
    };

    const findCard = (cardId) => {
        const id = Number(cardId);
        for (const list of state.listas || []) {
            const card = (list.tarjetas || []).find((item) => Number(item.id) === id);
            if (card) return { card, list };
        }
        return null;
    };

    const openCardModal = (listId, cardId = null) => {
        if (!modal || !cardForm || !modalTitle) return;
        cardForm.reset();
        cardForm.elements.lista_id.value = listId ? String(listId) : '';
        cardForm.elements.tarjeta_id.value = '';

        if (cardId) {
            const found = findCard(cardId);
            if (!found) return;
            modalTitle.textContent = 'Editar tarjeta';
            cardForm.elements.lista_id.value = String(found.list.id);
            cardForm.elements.tarjeta_id.value = String(found.card.id);
            cardForm.elements.titulo.value = found.card.titulo || '';
            cardForm.elements.descripcion.value = found.card.descripcion || '';
            deleteCardModalButton?.classList.remove('notas-hidden');
        } else {
            modalTitle.textContent = 'Nueva tarjeta';
            deleteCardModalButton?.classList.add('notas-hidden');
        }

        modal.classList.remove('notas-hidden');
        modal.setAttribute('aria-hidden', 'false');
        cardForm.elements.titulo.focus();
    };

    const closeCardModal = () => {
        if (!modal) return;
        modal.classList.add('notas-hidden');
        modal.setAttribute('aria-hidden', 'true');
    };

    board?.addEventListener('click', (event) => {
        const showFormButton = event.target.closest('[data-role="show-list-form"]');
        if (showFormButton) {
            showFormButton.outerHTML = renderListForm();
            board.querySelector('.notas-list-form input')?.focus();
            return;
        }

        if (event.target.closest('[data-role="cancel-list-form"]')) {
            render();
            return;
        }

        const addCardButton = event.target.closest('[data-role="add-card"]');
        if (addCardButton) {
            openCardModal(addCardButton.dataset.listId);
            return;
        }

        const editCardButton = event.target.closest('[data-role="edit-card"]');
        if (editCardButton) {
            openCardModal(null, editCardButton.dataset.cardId);
            return;
        }

        const deleteListButton = event.target.closest('[data-role="delete-list"]');
        if (deleteListButton) {
            if (!confirm('Eliminar esta lista y sus tarjetas?')) return;
            postAction('eliminar_lista', { lista_id: deleteListButton.dataset.listId }).catch((error) => alert(error.message));
        }
    });

    board?.addEventListener('submit', (event) => {
        const form = event.target.closest('[data-role="list-form"]');
        if (!form) return;

        event.preventDefault();
        const title = form.elements.titulo.value.trim();
        postAction('crear_lista', { titulo: title }).catch((error) => alert(error.message));
    });

    board?.addEventListener('change', (event) => {
        const toggle = event.target.closest('[data-role="toggle-card"]');
        if (toggle) {
            postAction('cambiar_estado_tarjeta', {
                tarjeta_id: toggle.dataset.cardId,
                completada: toggle.checked ? '1' : '0'
            }).catch((error) => alert(error.message));
        }
    });

    board?.addEventListener('blur', (event) => {
        const input = event.target.closest('[data-role="list-title"]');
        if (!input) return;

        const list = (state.listas || []).find((item) => Number(item.id) === Number(input.dataset.listId));
        const title = input.value.trim();
        if (!title) {
            input.value = list?.titulo || '';
            return;
        }
        if (list && title !== list.titulo) {
            postAction('actualizar_lista', { lista_id: list.id, titulo: title }).catch((error) => alert(error.message));
        }
    }, true);

    modal?.addEventListener('click', (event) => {
        if (event.target === modal || event.target.closest('[data-close-modal]')) {
            closeCardModal();
        }
    });

    cardForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        const tarjetaId = cardForm.elements.tarjeta_id.value;
        const listaId = cardForm.elements.lista_id.value;
        const payload = {
            tarjeta_id: tarjetaId,
            lista_id: listaId,
            titulo: cardForm.elements.titulo.value.trim(),
            descripcion: cardForm.elements.descripcion.value.trim()
        };
        const action = tarjetaId ? 'actualizar_tarjeta' : 'crear_tarjeta';
        postAction(action, payload)
            .then(closeCardModal)
            .catch((error) => alert(error.message));
    });

    searchInput?.addEventListener('input', () => {
        searchText = normalizeText(searchInput.value);
        render();
    });

    deleteCardModalButton?.addEventListener('click', () => {
        const tarjetaId = cardForm?.elements.tarjeta_id.value;
        if (!tarjetaId || !confirm('Eliminar esta tarjeta?')) return;

        postAction('eliminar_tarjeta', { tarjeta_id: tarjetaId })
            .then(closeCardModal)
            .catch((error) => alert(error.message));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeCardModal();
        }
    });

    fetchBoard().catch((error) => {
        setStatus(error.message);
    });
    window.setInterval(refreshBoardSilently, 5000);
});
