// ============================================
// Product Management — AJAX CRUD
// ============================================
// NOTE: write controls (add/edit/delete, categories) only exist in the DOM
// for admin users (CAN_WRITE) — every handler below is null-guarded so the
// script keeps working for view-only users (QR, tabs).

const productModalEl = document.getElementById('productModal');
const form           = document.getElementById('productForm');
const formError      = document.getElementById('formError');
const modalTitle     = document.getElementById('modalTitle');
const catSelect      = document.getElementById('productCategory');
const subCatSelect   = document.getElementById('productSubCategory');
const unitSelect     = document.getElementById('productUnit');

function getProductModal() {
    return bootstrap.Modal.getOrCreateInstance(productModalEl);
}

function escHtml(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Sub-category dependent dropdown ───────────────────────────────────────────

// SUBCATS is a { categoryId: [{id, name}, …] } map injected by the page.
function subcatOptionsHtml(categoryId) {
    let html = '<option value="">— None —</option>';
    (SUBCATS[categoryId] || []).forEach(sc => {
        html += `<option value="${sc.id}">${escHtml(sc.name)}</option>`;
    });
    return html;
}

function rebuildSubcatSelect(categoryId, selectedId = '') {
    if (!subCatSelect) return;
    if (typeof tsRebuild === 'function') {
        tsRebuild(subCatSelect, subcatOptionsHtml(categoryId), String(selectedId || ''));
    } else {
        subCatSelect.innerHTML = subcatOptionsHtml(categoryId);
        subCatSelect.value = String(selectedId || '');
    }
}

if (catSelect) {
    catSelect.addEventListener('change', () => rebuildSubcatSelect(catSelect.value));
}

// ── Open modal for ADD ────────────────────────────────────────────────────────
const btnAddProduct = document.getElementById('btnAddProduct');
if (btnAddProduct) {
    btnAddProduct.addEventListener('click', () => {
        form.reset();
        tsSyncForm(form);
        rebuildSubcatSelect('');
        document.getElementById('productId').value = '';
        document.getElementById('minStock').value  = '0';
        document.getElementById('wholesalePrice').value = '0';
        document.getElementById('currentImageNote').textContent = '';
        modalTitle.innerHTML = '<i class="bi bi-box-seam me-1 text-danger"></i> New product';
        formError.classList.add('d-none');
        getProductModal().show();
    });
}

// ── Open modal for EDIT ───────────────────────────────────────────────────────
document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', async () => {
        const id = btn.dataset.id;
        formError.classList.add('d-none');
        try {
            const res  = await fetch(`${BASE}/api/get_products.php?id=${id}`);
            const data = await res.json();
            if (!data.success) { showToast(data.message, 'danger'); return; }

            const p = data.product;
            form.reset();
            document.getElementById('productId').value   = p.id;
            tsSet(catSelect,  String(p.category_id), true);
            rebuildSubcatSelect(p.category_id, p.sub_category_id || '');
            document.getElementById('productCode').value  = p.product_code || '';
            document.getElementById('productName').value  = p.name;
            document.getElementById('sizeBrand').value    = p.size_brand || '';
            tsSet(unitSelect, p.unit, true);
            document.getElementById('buyPrice').value       = p.buy_price;
            document.getElementById('sellPrice').value      = p.sell_price;
            document.getElementById('wholesalePrice').value = p.wholesale_price || 0;
            document.getElementById('minStock').value       = p.min_stock;
            document.getElementById('currentImageNote').textContent =
                p.image_path ? 'Current image is kept unless you choose a new one.' : '';

            modalTitle.innerHTML = '<i class="bi bi-pencil me-1 text-danger"></i> Edit product';
            getProductModal().show();
        } catch {
            showToast('Data did not load.', 'danger');
        }
    });
});

// ── Submit (Add or Update) ────────────────────────────────────────────────────
if (form) {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        formError.classList.add('d-none');

        const id  = document.getElementById('productId').value;
        const url = id ? `${BASE}/api/update_product.php` : `${BASE}/api/add_product.php`;
        const btn = document.getElementById('btnSave');
        const orig = btn.innerHTML;

        const buy  = parseFloat(document.getElementById('buyPrice').value);
        const sell = parseFloat(document.getElementById('sellPrice').value);
        if (buy <= 0 || sell <= 0) {
            formError.textContent = 'Purchase and sell price must be greater than 0.';
            formError.classList.remove('d-none');
            return;
        }

        btn.disabled  = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

        try {
            const res  = await fetch(url, { method: 'POST', body: new FormData(form) });
            const data = await res.json();

            if (data.success) {
                showToast(data.message, 'success');
                getProductModal().hide();
                setTimeout(() => location.reload(), 700);
            } else {
                formError.textContent = data.message;
                formError.classList.remove('d-none');
            }
        } catch {
            formError.textContent = 'There was a server problem.';
            formError.classList.remove('d-none');
        } finally {
            btn.disabled  = false;
            btn.innerHTML = orig;
        }
    });
}

// ── Delete product ────────────────────────────────────────────────────────────
document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', async () => {
        const id   = btn.dataset.id;
        const name = btn.dataset.name;
        if (!confirm(`"${name}" Do you want to delete this product?`)) return;

        try {
            const res  = await fetch(`${BASE}/api/delete_product.php`, {
                method:  'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body:    new URLSearchParams({ id })
            });
            const data = await res.json();
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => location.reload(), 700);
            } else {
                showToast(data.message, 'danger');
            }
        } catch {
            showToast('Could not delete.', 'danger');
        }
    });
});

// ── QR code view / print ──────────────────────────────────────────────────────
const qrModalEl = document.getElementById('qrModal');
let qrCurrent   = { code: '', name: '' };

document.querySelectorAll('.btn-qr').forEach(btn => {
    btn.addEventListener('click', () => {
        const code = btn.dataset.code;
        const name = btn.dataset.name;
        if (!code) { showToast('This product has no code yet.', 'warning'); return; }
        qrCurrent = { code, name };

        const container = document.getElementById('qrContainer');
        container.innerHTML = '';
        try {
            const qr = qrcode(0, 'M');   // qrcode-generator lib
            qr.addData(code);
            qr.make();
            container.innerHTML = qr.createImgTag(5, 8);
        } catch {
            container.textContent = code;
        }
        document.getElementById('qrProductName').textContent = name;
        document.getElementById('qrProductCode').textContent = code;
        bootstrap.Modal.getOrCreateInstance(qrModalEl).show();
    });
});

const btnPrintQr = document.getElementById('btnPrintQr');
if (btnPrintQr) {
    btnPrintQr.addEventListener('click', () => {
        const img = document.querySelector('#qrContainer img');
        if (!img) return;
        const w = window.open('', '_blank', 'width=400,height=500');
        w.document.write(`
            <html><head><title>QR — ${escHtml(qrCurrent.code)}</title></head>
            <body style="text-align:center;font-family:sans-serif;padding:24px">
                <img src="${img.src}" style="width:220px;height:220px"><br>
                <h3 style="margin:.6em 0 .2em">${escHtml(qrCurrent.name)}</h3>
                <code style="font-size:1.1em">${escHtml(qrCurrent.code)}</code>
                <script>window.onload = () => { window.print(); window.close(); };<\/script>
            </body></html>`);
        w.document.close();
    });
}

// ══ Category Management (admin only — elements may be absent) ═════════════════

const catError = document.getElementById('catError');
const catList  = document.getElementById('categoryList');
const catInput = document.getElementById('newCategoryName');

const btnAddCategory = document.getElementById('btnAddCategory');
if (btnAddCategory) {
    btnAddCategory.addEventListener('click', async () => {
        const name = catInput.value.trim();
        if (!name) { catError.textContent = 'Enter the category name.'; catError.classList.remove('d-none'); return; }
        catError.classList.add('d-none');

        try {
            const res  = await fetch(`${BASE}/api/add_category.php`, {
                method: 'POST',
                body:   new URLSearchParams({ name })
            });
            const data = await res.json();
            if (data.success) {
                catInput.value = '';
                showToast(data.message, 'success');
                appendCategoryRow(data.data ? data.data.id : data.id, name);
            } else {
                catError.textContent = data.message;
                catError.classList.remove('d-none');
            }
        } catch {
            catError.textContent = 'There was a server problem.';
            catError.classList.remove('d-none');
        }
    });

    catInput.addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); btnAddCategory.click(); }
    });
}

function appendCategoryRow(id, name) {
    const noCatMsg = document.getElementById('noCatMsg');
    if (noCatMsg) noCatMsg.remove();

    const li = document.createElement('li');
    li.className = 'list-group-item d-flex justify-content-between align-items-center py-2';
    li.innerHTML = `<span>${escHtml(name)}</span>
        <button class="btn btn-sm btn-outline-danger btn-del-cat"
                data-id="${id}" data-name="${escHtml(name)}">
            <i class="bi bi-trash"></i>
        </button>`;
    catList.appendChild(li);
    li.querySelector('.btn-del-cat').addEventListener('click', deleteCategoryHandler);

    // Also append to product modal select + sub-category parent select
    const opt = document.createElement('option');
    opt.value       = id;
    opt.textContent = name;
    catSelect.appendChild(opt);
    if (catSelect.tomselect) catSelect.tomselect.addOption({ value: String(id), text: name });

    const parentSel = document.getElementById('subcatParent');
    if (parentSel) {
        const o2 = document.createElement('option');
        o2.value = id; o2.textContent = name;
        parentSel.appendChild(o2);
    }
}

// ── Sub-category management (inside category modal) ───────────────────────────

const subcatParent = document.getElementById('subcatParent');
const subcatList   = document.getElementById('subcatList');
const subcatInput  = document.getElementById('newSubcatName');
const btnAddSubcat = document.getElementById('btnAddSubcat');
const subcatError  = document.getElementById('subcatError');

function renderSubcatList(categoryId) {
    const items = SUBCATS[categoryId] || [];
    if (!items.length) {
        subcatList.innerHTML = '<li class="list-group-item text-muted text-center small">No sub-categories</li>';
        return;
    }
    subcatList.innerHTML = items.map(sc => `
        <li class="list-group-item d-flex justify-content-between align-items-center py-1">
            <span class="small">${escHtml(sc.name)}</span>
            <button class="btn btn-sm btn-outline-danger btn-del-subcat" data-id="${sc.id}" data-name="${escHtml(sc.name)}">
                <i class="bi bi-trash"></i>
            </button>
        </li>`).join('');
    subcatList.querySelectorAll('.btn-del-subcat').forEach(b =>
        b.addEventListener('click', deleteSubcatHandler));
}

if (subcatParent) {
    subcatParent.addEventListener('change', () => {
        const cid = subcatParent.value;
        subcatInput.disabled  = !cid;
        btnAddSubcat.disabled = !cid;
        subcatError.classList.add('d-none');
        if (cid) renderSubcatList(cid);
        else subcatList.innerHTML = '<li class="list-group-item text-muted text-center small">Select a category above</li>';
    });

    btnAddSubcat.addEventListener('click', async () => {
        const cid  = subcatParent.value;
        const name = subcatInput.value.trim();
        if (!cid) return;
        if (!name) { subcatError.textContent = 'Enter the sub-category name.'; subcatError.classList.remove('d-none'); return; }
        subcatError.classList.add('d-none');

        try {
            const res  = await fetch(`${BASE}/api/add_subcategory.php`, {
                method: 'POST',
                body:   new URLSearchParams({ category_id: cid, name })
            });
            const data = await res.json();
            if (data.success) {
                subcatInput.value = '';
                if (!SUBCATS[cid]) SUBCATS[cid] = [];
                SUBCATS[cid].push({ id: data.id, name });
                SUBCATS[cid].sort((a, b) => a.name.localeCompare(b.name));
                renderSubcatList(cid);
                showToast(data.message, 'success');
                // Keep the product-form dropdown in sync when same category is open
                if (catSelect && catSelect.value === cid) rebuildSubcatSelect(cid, subCatSelect ? subCatSelect.value : '');
            } else {
                subcatError.textContent = data.message;
                subcatError.classList.remove('d-none');
            }
        } catch {
            subcatError.textContent = 'There was a server problem.';
            subcatError.classList.remove('d-none');
        }
    });

    subcatInput.addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); btnAddSubcat.click(); }
    });
}

async function deleteSubcatHandler(e) {
    const btn  = e.currentTarget;
    const id   = btn.dataset.id;
    const name = btn.dataset.name;
    if (!confirm(`Delete sub-category "${name}"?\nProducts keep working — they are just detached from it.`)) return;

    try {
        const res  = await fetch(`${BASE}/api/delete_subcategory.php`, {
            method: 'POST',
            body:   new URLSearchParams({ id })
        });
        const data = await res.json();
        if (data.success) {
            const cid = subcatParent.value;
            SUBCATS[cid] = (SUBCATS[cid] || []).filter(sc => String(sc.id) !== String(id));
            renderSubcatList(cid);
            showToast(data.message, 'success');
            if (catSelect && catSelect.value === cid) rebuildSubcatSelect(cid);
        } else {
            showToast(data.message, 'danger');
        }
    } catch {
        showToast('Could not delete.', 'danger');
    }
}

// ── Typed-confirmation category delete ────────────────────────────────────────

const delCatModalEl = document.getElementById('delCatModal');
const delCatInput   = document.getElementById('delCatConfirmInput');
const delCatError   = document.getElementById('delCatError');
const btnConfirmDel = document.getElementById('btnConfirmDelCat');
let   delCatTarget  = { id: null, name: '', li: null };

function deleteCategoryHandler(e) {
    const btn = e.currentTarget;
    delCatTarget = { id: btn.dataset.id, name: btn.dataset.name, li: btn.closest('li') };

    document.getElementById('delCatName').textContent = delCatTarget.name;
    document.getElementById('delCatId').value          = delCatTarget.id;
    delCatInput.value = '';
    delCatError.classList.add('d-none');
    btnConfirmDel.disabled = true;

    // Bootstrap 5 doesn't support stacked modals — close the parent first.
    const catModalEl   = document.getElementById('categoryModal');
    const catModalInst = bootstrap.Modal.getInstance(catModalEl);
    const delCatModal  = bootstrap.Modal.getOrCreateInstance(delCatModalEl);
    if (catModalInst) {
        catModalEl.addEventListener('hidden.bs.modal', () => {
            delCatModal.show();
            setTimeout(() => delCatInput.focus(), 300);
        }, { once: true });
        catModalInst.hide();
    } else {
        delCatModal.show();
        setTimeout(() => delCatInput.focus(), 300);
    }
}

if (delCatModalEl) {
    // Enable the confirm button only when the typed name matches exactly.
    delCatInput.addEventListener('input', () => {
        btnConfirmDel.disabled = delCatInput.value.trim() !== delCatTarget.name;
    });
    delCatInput.addEventListener('keydown', e => {
        if (e.key === 'Enter' && !btnConfirmDel.disabled) { e.preventDefault(); btnConfirmDel.click(); }
    });

    btnConfirmDel.addEventListener('click', async () => {
        if (delCatInput.value.trim() !== delCatTarget.name) {
            delCatError.textContent = 'Name does not match. Type the exact same name.';
            delCatError.classList.remove('d-none');
            return;
        }
        const { id, li } = delCatTarget;
        btnConfirmDel.disabled = true;

        try {
            const res  = await fetch(`${BASE}/api/delete_category.php`, {
                method: 'POST',
                body:   new URLSearchParams({ id })
            });
            const data = await res.json();
            if (data.success) {
                if (li) li.remove();
                showToast(data.message, 'success');
                const opt = catSelect.querySelector(`option[value="${id}"]`);
                if (opt) opt.remove();
                if (catSelect.tomselect) catSelect.tomselect.removeOption(String(id));
                bootstrap.Modal.getOrCreateInstance(delCatModalEl).hide();
            } else {
                delCatError.textContent = data.message;
                delCatError.classList.remove('d-none');
                btnConfirmDel.disabled = false;
            }
        } catch {
            delCatError.textContent = 'Could not delete.';
            delCatError.classList.remove('d-none');
            btnConfirmDel.disabled = false;
        }
    });

    document.querySelectorAll('.btn-del-cat').forEach(btn => {
        btn.addEventListener('click', deleteCategoryHandler);
    });
}
