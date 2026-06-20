/* global BASE_URL, CAN_WRITE */

let searchTimer  = null;
let activeStatus = '';   // '' | 'pending' | 'done'

const NOTES_PAGE_SIZE = 20;
let _allNotes  = [];
let _notesPage = 1;

function esc(s) {
    return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

// ── Load ─────────────────────────────────────────────────────────────────────
function loadNotes() {
    const search = document.getElementById('searchInput').value.trim();
    const list   = document.getElementById('notesList');
    list.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary spinner-border-sm"></div></div>';

    const params = new URLSearchParams();
    if (search)       params.set('search', search);
    if (activeStatus) params.set('status', activeStatus);

    fetch(BASE_URL + '/api/get_free_notes.php?' + params.toString())
        .then(r => r.json())
        .then(res => {
            if (res.success) renderNotes(res.notes);
            else list.innerHTML = `<div class="alert alert-danger">${esc(res.message)}</div>`;
        })
        .catch(() => {
            list.innerHTML = '<div class="alert alert-danger">There was a problem loading the data.</div>';
        });
}

// ── Render ───────────────────────────────────────────────────────────────────
function renderNotes(notes) {
    notesCache = {};
    notes.forEach(n => { notesCache[n.id] = n; });
    _allNotes  = notes;
    _notesPage = 1;
    renderNotesPage(1);
}

function renderNotesPage(page) {
    _notesPage = page;
    const list = document.getElementById('notesList');
    let bar    = document.getElementById('notesPaginationBar');

    if (!_allNotes.length) {
        list.innerHTML = `
        <div class="text-center py-5 text-muted">
            <i class="bi bi-journal-x fs-1 d-block mb-2 opacity-25"></i>
            No notes found
        </div>`;
        if (bar) bar.style.display = 'none';
        return;
    }

    const totalPages = Math.ceil(_allNotes.length / NOTES_PAGE_SIZE);
    const start      = (page - 1) * NOTES_PAGE_SIZE;
    const pageData   = _allNotes.slice(start, start + NOTES_PAGE_SIZE);

    list.innerHTML = pageData.map(n => noteCard(n)).join('');

    // Create bar if missing
    if (!bar) {
        bar = document.createElement('div');
        bar.id        = 'notesPaginationBar';
        bar.className = 'd-flex justify-content-between align-items-center py-2 mt-2';
        list.insertAdjacentElement('afterend', bar);
    }

    const from = start + 1;
    const to   = Math.min(start + NOTES_PAGE_SIZE, _allNotes.length);
    const infoText = `${_allNotes.length} out of ${from}–${to} Showing`;

    if (totalPages <= 1) {
        bar.style.display = 'none';
        return;
    }
    bar.style.removeProperty('display');

    let html = `<small class="text-muted">${infoText}</small><nav><ul class="pagination pagination-sm mb-0">`;
    html += `<li class="page-item ${page===1?'disabled':''}"><a class="page-link" href="#" onclick="event.preventDefault();renderNotesPage(${page-1})">&#8249;</a></li>`;
    for (let i = 1; i <= totalPages; i++) {
        if (totalPages > 7 && i > 2 && i < totalPages-1 && Math.abs(i-page) > 1) {
            if (i === 3 || i === totalPages-2) html += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
            continue;
        }
        html += `<li class="page-item ${i===page?'active':''}"><a class="page-link" href="#" onclick="event.preventDefault();renderNotesPage(${i})">${i}</a></li>`;
    }
    html += `<li class="page-item ${page===totalPages?'disabled':''}"><a class="page-link" href="#" onclick="event.preventDefault();renderNotesPage(${page+1})">&#8250;</a></li>`;
    html += `</ul></nav>`;
    bar.innerHTML = html;
}

function noteCard(n) {
    const pinned   = parseInt(n.is_pinned) === 1;
    const isDone   = n.status === 'done';
    const pinnedBorder = pinned ? 'border-warning border-2' : '';
    const fadedText    = isDone ? 'opacity-75' : '';

    const statusBadge = isDone
        ? `<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Done</span>`
        : `<span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Pending</span>`;

    const pinBadge = pinned
        ? `<span class="badge bg-warning text-dark ms-1"><i class="bi bi-pin-angle-fill"></i> Pin</span>`
        : '';

    const actions = CAN_WRITE ? `
        <div class="d-flex gap-1 flex-shrink-0 ms-2">
            <!-- Pin toggle -->
            <button class="btn btn-sm ${pinned ? 'btn-warning' : 'btn-outline-secondary'}"
                    onclick="togglePin(${n.id})" title="${pinned ? 'Unpin' : 'Pin'}">
                <i class="bi bi-pin-angle${pinned ? '-fill' : ''}"></i>
            </button>
            <!-- Status toggle -->
            ${isDone
                ? `<button class="btn btn-sm btn-outline-warning" onclick="setStatus(${n.id},'pending')" title="Mark pending">
                       <i class="bi bi-arrow-counterclockwise"></i>
                   </button>`
                : `<button class="btn btn-sm btn-outline-success" onclick="setStatus(${n.id},'done')" title="Mark as done">
                       <i class="bi bi-check-lg"></i>
                   </button>`
            }
            <!-- Edit -->
            <button class="btn btn-sm btn-outline-primary" onclick="openEdit(${n.id})" title="Edit">
                <i class="bi bi-pencil"></i>
            </button>
            <!-- Delete -->
            <button class="btn btn-sm btn-outline-danger" onclick="deleteNote(${n.id})" title="Delete">
                <i class="bi bi-trash"></i>
            </button>
        </div>` : '';

    return `
    <div class="card shadow-sm mb-3 note-card ${pinnedBorder}" id="note-${n.id}">
        <div class="card-body ${isDone ? 'bg-light' : ''}">
            <div class="d-flex justify-content-between align-items-start gap-1">
                <div class="flex-grow-1 ${fadedText}">
                    <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                        <span class="fw-bold text-primary">
                            <i class="bi bi-person-circle me-1"></i>${esc(n.customer_name)}
                        </span>
                        ${statusBadge}
                        ${pinBadge}
                        <span class="badge bg-light text-dark border">
                            <i class="bi bi-calendar3 me-1"></i>${esc(n.note_date)}
                        </span>
                        <span class="text-muted small">
                            <i class="bi bi-pencil me-1"></i>${esc(n.author)}
                        </span>
                    </div>
                    <div style="white-space:pre-wrap;line-height:1.7;${isDone ? 'text-decoration:line-through;color:#6c757d' : ''}">${esc(n.note)}</div>
                </div>
                ${actions}
            </div>
        </div>
    </div>`;
}

// ── Submit ───────────────────────────────────────────────────────────────────
function submitNote(e) {
    e.preventDefault();
    const name = document.getElementById('nCustomerName').value.trim();
    const note = document.getElementById('nText').value.trim();
    const date = document.getElementById('nDate').value;
    if (!name || !note) return;

    const btn = document.getElementById('noteSaveBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';

    fetch(BASE_URL + '/api/add_free_note.php', {
        method: 'POST',
        body:   new URLSearchParams({ customer_name: name, note, note_date: date })
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Save';
        if (res.success) {
            document.getElementById('noteForm').reset();
            document.getElementById('nDate').value = new Date().toISOString().slice(0, 10);
            document.getElementById('charCount').textContent = '0';
            showToast(res.message, 'success');
            loadNotes();
        } else {
            showToast(res.message, 'danger');
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Save';
        showToast('Something went wrong.', 'danger');
    });
}

// ── Pin toggle ───────────────────────────────────────────────────────────────
function togglePin(id) {
    fetch(BASE_URL + '/api/update_free_note.php', {
        method: 'POST',
        body:   new URLSearchParams({ id, action: 'toggle_pin' })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) { showToast(res.message, 'success'); loadNotes(); }
        else showToast(res.message, 'danger');
    })
    .catch(() => showToast('Something went wrong.', 'danger'));
}

// ── Status change ────────────────────────────────────────────────────────────
function setStatus(id, status) {
    fetch(BASE_URL + '/api/update_free_note.php', {
        method: 'POST',
        body:   new URLSearchParams({ id, action: 'set_status', status })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) { showToast(res.message, 'success'); loadNotes(); }
        else showToast(res.message, 'danger');
    })
    .catch(() => showToast('Something went wrong.', 'danger'));
}

// ── Edit ─────────────────────────────────────────────────────────────────────
let editModal = null;
let notesCache = {};

function openEdit(id) {
    const card = document.getElementById('note-' + id);
    if (!card) return;
    // Read data from rendered card via cache
    const data = notesCache[id];
    if (!data) return;

    document.getElementById('eNoteId').value       = data.id;
    document.getElementById('eCustomerName').value = data.customer_name;
    document.getElementById('eDate').value         = data.note_date;
    document.getElementById('eText').value         = data.note;

    if (!editModal) editModal = new bootstrap.Modal(document.getElementById('editNoteModal'));
    editModal.show();
}

function submitEdit(e) {
    e.preventDefault();
    const id   = document.getElementById('eNoteId').value;
    const name = document.getElementById('eCustomerName').value.trim();
    const note = document.getElementById('eText').value.trim();
    const date = document.getElementById('eDate').value;
    if (!name || !note) return;

    const btn = document.getElementById('editSaveBtn');
    btn.disabled = true;

    fetch(BASE_URL + '/api/edit_free_note.php', {
        method: 'POST',
        body:   new URLSearchParams({ id, customer_name: name, note, note_date: date })
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        if (res.success) {
            editModal.hide();
            showToast(res.message, 'success');
            loadNotes();
        } else {
            showToast(res.message, 'danger');
        }
    })
    .catch(() => { btn.disabled = false; showToast('Something went wrong.', 'danger'); });
}

// ── Delete ───────────────────────────────────────────────────────────────────
function deleteNote(id) {
    if (!confirm('Delete this note?')) return;
    fetch(BASE_URL + '/api/delete_free_note.php', {
        method: 'POST',
        body:   new URLSearchParams({ id })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showToast(res.message, 'success');
            loadNotes();
        } else {
            showToast(res.message, 'danger');
        }
    })
    .catch(() => showToast('There was a problem deleting.', 'danger'));
}

// ── Filter tabs ──────────────────────────────────────────────────────────────
document.querySelectorAll('#statusFilter button').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('#statusFilter button').forEach(b => {
            b.classList.remove('active', 'btn-danger', 'btn-warning', 'btn-success');
            const s = b.dataset.status;
            b.classList.add(s === 'pending' ? 'btn-outline-warning'
                          : s === 'done'    ? 'btn-outline-success'
                          :                   'btn-outline-secondary');
        });
        this.classList.remove('btn-outline-warning', 'btn-outline-success', 'btn-outline-secondary');
        const s = this.dataset.status;
        this.classList.add('active', s === 'pending' ? 'btn-warning'
                                   : s === 'done'    ? 'btn-success'
                                   :                   'btn-danger');
        activeStatus = s;
        loadNotes();
    });
});

// ── Search ───────────────────────────────────────────────────────────────────
function clearSearch() {
    document.getElementById('searchInput').value = '';
    loadNotes();
}

document.getElementById('searchInput').addEventListener('input', function () {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(loadNotes, 350);
});

// ── Char counter ─────────────────────────────────────────────────────────────
const nText = document.getElementById('nText');
if (nText) {
    nText.addEventListener('input', () => {
        document.getElementById('charCount').textContent = nText.value.length;
    });
}

// Initial load
loadNotes();
