// ============================================
// Customer Management — AJAX CRUD
// ============================================

function getCModal() {
    return bootstrap.Modal.getOrCreateInstance(document.getElementById('customerModal'));
}

// ---- Helpers ----
function esc(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
function jsEsc(str) {
    return String(str ?? '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
}
function fmt(n) {
    return parseFloat(n || 0).toLocaleString('en-IN', {
        minimumFractionDigits: 2, maximumFractionDigits: 2
    }) + ' ৳';
}

// ---- Modal ----
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'New customer';
    document.getElementById('customerForm').reset();
    document.getElementById('customerId').value = '';
    getCModal().show();
}

function openEditModal(id, name, phone, address) {
    document.getElementById('modalTitle').textContent = 'Edit customer';
    document.getElementById('customerId').value      = id;
    document.getElementById('customerName').value    = name;
    document.getElementById('customerPhone').value   = phone;
    document.getElementById('customerAddress').value = address;
    getCModal().show();
}

function submitCustomer(e) {
    e.preventDefault();
    const id  = document.getElementById('customerId').value;
    const url = id
        ? BASE_URL + '/api/update_customer.php'
        : BASE_URL + '/api/add_customer.php';

    const data = {
        id:      id,
        name:    document.getElementById('customerName').value,
        phone:   document.getElementById('customerPhone').value,
        address: document.getElementById('customerAddress').value,
    };

    const btn = document.getElementById('saveBtn');
    btn.disabled = true;
    ajaxPost(url, data, res => {
        btn.disabled = false;
        if (res.success) {
            getCModal().hide();
            showToast(res.message, 'success');
            loadCustomers();
        } else {
            showToast(res.message, 'danger');
        }
    });
}

// ---- Delete ----
function deleteCustomer(id, name) {
    if (!confirm(`"${name}" Delete?\nThis action cannot be undone.`)) return;
    ajaxPost(BASE_URL + '/api/delete_customer.php', { id }, res => {
        showToast(res.message, res.success ? 'success' : 'danger');
        if (res.success) loadCustomers();
    });
}

// ---- Pagination state ----
const CUST_PAGE_SIZE = 50;
let _allCustomers    = [];
let _filteredCust    = [];
let _custPage        = 1;

// ---- Load & Render ----
function loadCustomers() {
    fetch(BASE_URL + '/api/get_customers.php')
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                _allCustomers = res.data;
                _filteredCust = res.data;
                _custPage     = 1;
                renderCustomersPage(1);
            }
        })
        .catch(() => showToast('There was a problem loading the data', 'danger'));
}

function renderCustomers(list) {
    _allCustomers = list;
    _filteredCust = list;
    _custPage     = 1;
    renderCustomersPage(1);
}

function renderCustomersPage(page) {
    _custPage = page;
    const tbody = document.getElementById('customersBody');

    if (!_filteredCust.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5 text-muted">No customers</td></tr>';
        document.getElementById('custPaginationBar').style.display = 'none';
        return;
    }

    const totalPages = Math.ceil(_filteredCust.length / CUST_PAGE_SIZE);
    const start      = (page - 1) * CUST_PAGE_SIZE;
    const pageData   = _filteredCust.slice(start, start + CUST_PAGE_SIZE);

    tbody.innerHTML = pageData.map((c, i) => `
        <tr>
            <td class="text-muted">${start + i + 1}</td>
            <td class="fw-semibold">${esc(c.name)}</td>
            <td>${esc(c.phone || '—')}</td>
            <td class="text-muted small">${esc(c.address || '—')}</td>
            <td class="text-end">${fmt(c.total_purchase)}</td>
            <td class="text-end">
                ${parseFloat(c.total_due) > 0
                    ? `<span class="badge bg-danger">${fmt(c.total_due)}</span>`
                    : '<span class="text-success small">Paid</span>'}
            </td>
            <td class="text-center">
                <button class="btn btn-sm btn-outline-primary me-1 btn-edit-cust"
                    data-id="${c.id}"
                    data-name="${esc(c.name)}"
                    data-phone="${esc(c.phone || '')}"
                    data-address="${esc(c.address || '')}">
                    <i class="bi bi-pencil"></i>
                </button>
                ${IS_ADMIN && parseInt(c.id) !== 1 ? `
                <button class="btn btn-sm btn-outline-danger btn-del-cust"
                    data-id="${c.id}"
                    data-name="${esc(c.name)}">
                    <i class="bi bi-trash"></i>
                </button>` : ''}
            </td>
        </tr>
    `).join('');

    // Pagination bar
    const bar  = document.getElementById('custPaginationBar');
    const info = document.getElementById('custPageInfo');
    const nav  = document.getElementById('custPagination');
    const from = start + 1;
    const to   = Math.min(start + CUST_PAGE_SIZE, _filteredCust.length);
    info.textContent = `${_filteredCust.length} out of ${from}–${to} Showing`;

    if (totalPages <= 1) {
        bar.style.display = 'none';
        return;
    }

    bar.style.removeProperty('display');

    let pages = '';
    pages += `<li class="page-item ${page === 1 ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="event.preventDefault();renderCustomersPage(${page - 1})">&#8249;</a></li>`;
    for (let i = 1; i <= totalPages; i++) {
        if (totalPages > 7 && i > 2 && i < totalPages - 1 && Math.abs(i - page) > 1) {
            if (i === 3 || i === totalPages - 2) pages += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
            continue;
        }
        pages += `<li class="page-item ${i === page ? 'active' : ''}">
            <a class="page-link" href="#" onclick="event.preventDefault();renderCustomersPage(${i})">${i}</a></li>`;
    }
    pages += `<li class="page-item ${page === totalPages ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="event.preventDefault();renderCustomersPage(${page + 1})">&#8250;</a></li>`;
    nav.innerHTML = pages;
}

// ---- Live Search ----
document.getElementById('searchInput').addEventListener('input', function () {
    const q = this.value.toLowerCase().trim();
    _filteredCust = q
        ? _allCustomers.filter(c =>
            (c.name  || '').toLowerCase().includes(q) ||
            (c.phone || '').toLowerCase().includes(q))
        : _allCustomers;
    _custPage = 1;
    renderCustomersPage(1);
});

// ---- Delegated edit / delete handlers (work on dynamically rendered rows) ----
document.getElementById('customersBody').addEventListener('click', function (e) {
    const editBtn = e.target.closest('.btn-edit-cust');
    if (editBtn) {
        openEditModal(
            editBtn.dataset.id,
            editBtn.dataset.name,
            editBtn.dataset.phone,
            editBtn.dataset.address
        );
        return;
    }
    const delBtn = e.target.closest('.btn-del-cust');
    if (delBtn) {
        deleteCustomer(delBtn.dataset.id, delBtn.dataset.name);
    }
});

// ---- Init ----
loadCustomers();
