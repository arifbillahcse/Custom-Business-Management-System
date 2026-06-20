/* global BASE_URL, CAN_WRITE */
let activeStatus='', searchTimer=null, currentPlanId=null;
const planModal   = new bootstrap.Modal(document.getElementById('planModal'));
const detailModal = new bootstrap.Modal(document.getElementById('planDetailModal'));
const payModal    = new bootstrap.Modal(document.getElementById('payModal'));

function esc(s) { return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function fmt(v) { return parseFloat(v||0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2})+' ৳'; }

// ── Load plans ────────────────────────────────────────────────────────────────
function loadPlans() {
    const search = document.getElementById('searchInput').value.trim();
    const p = new URLSearchParams();
    if (activeStatus) p.set('status', activeStatus);
    if (search)       p.set('search', search);
    const el = document.getElementById('planList');
    el.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
    fetch(BASE_URL+'/api/get_installment_plans.php?'+p).then(r=>r.json()).then(res=>{
        if (!res.success) { el.innerHTML='<div class="alert alert-danger">Load failed.</div>'; return; }
        renderPlans(res.data);
    }).catch(()=>{ el.innerHTML='<div class="alert alert-danger">Something went wrong.</div>'; });
}

const INST_PAGE_SIZE = 50;
let _allPlans        = [];
let _instPage        = 1;

function renderPlans(plans) {
    _allPlans = plans;
    _instPage = 1;
    renderInstPage(1);
}

function renderInstPage(page) {
    _instPage = page;
    const el  = document.getElementById('planList');
    const bar = document.getElementById('instPaginationBar');

    if (!_allPlans.length) {
        el.innerHTML = '<div class="text-center py-5 text-muted"><i class="bi bi-calendar-x fs-1 d-block opacity-25 mb-2"></i>No installment plans</div>';
        bar.style.display = 'none';
        return;
    }

    const totalPages = Math.ceil(_allPlans.length / INST_PAGE_SIZE);
    const start      = (page - 1) * INST_PAGE_SIZE;
    const pageData   = _allPlans.slice(start, start + INST_PAGE_SIZE);

    const badge = { active:'<span class="badge bg-primary">Active</span>',
                    completed:'<span class="badge bg-success">Completed</span>',
                    cancelled:'<span class="badge bg-secondary">Cancelled</span>' };

    el.innerHTML = `<div class="table-responsive"><table class="table table-hover shadow-sm">
        <thead class="table-dark"><tr>
            <th>Customer</th><th>Start</th><th class="text-end">Total</th>
            <th class="text-end">Paid</th><th>Progress</th><th>Status</th><th></th>
        </tr></thead><tbody>` +
    pageData.map(p => {
        const pct = p.total_inst > 0 ? Math.round((parseInt(p.paid_inst)/parseInt(p.total_inst))*100) : 0;
        return `<tr>
            <td class="fw-semibold">${esc(p.customer_name)}</td>
            <td>${esc(p.start_date)}</td>
            <td class="text-end">${fmt(p.total_amount)}</td>
            <td class="text-end text-success">${fmt(p.total_paid)}</td>
            <td style="min-width:120px">
                <div class="d-flex align-items-center gap-2">
                    <div class="progress flex-grow-1" style="height:8px">
                        <div class="progress-bar bg-success" style="width:${pct}%"></div>
                    </div>
                    <small class="text-muted">${p.paid_inst}/${p.total_inst}</small>
                </div>
            </td>
            <td>${badge[p.status]||''}</td>
            <td><button class="btn btn-sm btn-outline-primary" onclick="viewPlan(${p.id})">
                <i class="bi bi-eye"></i></button></td>
        </tr>`;
    }).join('') + '</tbody></table></div>';

    const from = start + 1;
    const to   = Math.min(start + INST_PAGE_SIZE, _allPlans.length);
    document.getElementById('instPageInfo').textContent = `${_allPlans.length} out of ${from}–${to} Showing`;

    if (totalPages <= 1) { bar.style.display = 'none'; return; }
    bar.style.removeProperty('display');

    let html = `<li class="page-item ${page===1?'disabled':''}"><a class="page-link" href="#" onclick="event.preventDefault();renderInstPage(${page-1})">&#8249;</a></li>`;
    for (let i = 1; i <= totalPages; i++) {
        if (totalPages > 7 && i > 2 && i < totalPages-1 && Math.abs(i-page) > 1) {
            if (i === 3 || i === totalPages-2) html += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
            continue;
        }
        html += `<li class="page-item ${i===page?'active':''}"><a class="page-link" href="#" onclick="event.preventDefault();renderInstPage(${i})">${i}</a></li>`;
    }
    html += `<li class="page-item ${page===totalPages?'disabled':''}"><a class="page-link" href="#" onclick="event.preventDefault();renderInstPage(${page+1})">&#8250;</a></li>`;
    document.getElementById('instPagination').innerHTML = html;
}

// ── View plan detail ──────────────────────────────────────────────────────────
function viewPlan(id) {
    currentPlanId = id;
    const body = document.getElementById('planDetailBody');
    body.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
    detailModal.show();
    fetch(BASE_URL+'/api/get_installment_plan.php?id='+id).then(r=>r.json()).then(res=>{
        if (!res.success) { body.innerHTML='<div class="alert alert-danger">'+esc(res.message)+'</div>'; return; }
        renderPlanDetail(res.data);
    });
}

function renderPlanDetail(p) {
    const body = document.getElementById('planDetailBody');
    const statusBadge = { pending:'<span class="badge bg-warning text-dark">Pending</span>',
                          paid:'<span class="badge bg-success">Paid</span>',
                          overdue:'<span class="badge bg-danger">Expired</span>' };
    const rows = (p.installments||[]).map(inst => `
    <tr class="${inst.status==='overdue'?'table-danger':inst.status==='paid'?'table-success':''}">
        <td>${inst.installment_no}</td>
        <td>${esc(inst.due_date)}</td>
        <td class="text-end">${fmt(inst.amount)}</td>
        <td class="text-end">${inst.status==='paid' ? fmt(inst.paid_amount) : '—'}</td>
        <td>${inst.paid_date||'—'}</td>
        <td>${statusBadge[inst.status]||''}</td>
        <td>${(CAN_WRITE && inst.status!=='paid') ? `<button class="btn btn-sm btn-success" onclick="openPay(${inst.id},${inst.installment_no},${inst.amount})"><i class="bi bi-cash-coin"></i></button>` : ''}</td>
    </tr>`).join('');

    body.innerHTML = `
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3 text-center">
            <div class="text-muted small">Customer</div><div class="fw-bold">${esc(p.customer_name)}</div>
        </div>
        <div class="col-6 col-md-3 text-center">
            <div class="text-muted small">Total amount</div><div class="fw-bold">${fmt(p.total_amount)}</div>
        </div>
        <div class="col-6 col-md-3 text-center">
            <div class="text-muted small">Advance</div><div class="fw-bold text-success">${fmt(p.down_payment)}</div>
        </div>
        <div class="col-6 col-md-3 text-center">
            <div class="text-muted small">Per installment</div><div class="fw-bold">${fmt(p.installment_amount)}</div>
        </div>
    </div>
    <div class="table-responsive">
    <table class="table table-sm table-bordered">
        <thead class="table-dark"><tr>
            <th>#</th><th>Due date</th><th class="text-end">Quantity</th>
            <th class="text-end">Paid</th><th>Payment date</th><th>Status</th><th></th>
        </tr></thead>
        <tbody>${rows}</tbody>
    </table></div>`;
}

// ── Pay installment ───────────────────────────────────────────────────────────
function openPay(id, no, amount) {
    document.getElementById('payInstId').value  = id;
    document.getElementById('payInstNo').textContent  = no;
    document.getElementById('payInstAmt').textContent = fmt(amount);
    document.getElementById('payAmount').value  = amount;
    document.getElementById('payNote').value    = '';
    payModal.show();
}
function submitPay(e) {
    e.preventDefault();
    const id     = document.getElementById('payInstId').value;
    const amount = document.getElementById('payAmount').value;
    const note   = document.getElementById('payNote').value.trim();
    const btn    = document.getElementById('paySaveBtn');
    btn.disabled = true;
    fetch(BASE_URL+'/api/pay_installment.php',{method:'POST',body:new URLSearchParams({id,amount,note})})
        .then(r=>r.json()).then(res=>{
            btn.disabled=false;
            if (res.success) { payModal.hide(); showToast(res.message,'success'); viewPlan(currentPlanId); loadPlans(); }
            else showToast(res.message,'danger');
        }).catch(()=>{ btn.disabled=false; showToast('Something went wrong.','danger'); });
}

// ── New plan ──────────────────────────────────────────────────────────────────
function calcInstall() {
    const total = parseFloat(document.getElementById('pTotal')?.value||0);
    const down  = parseFloat(document.getElementById('pDown')?.value||0);
    const count = parseInt(document.getElementById('pCount')?.value||0);
    const prev  = document.getElementById('installPreview');
    if (total > 0 && count > 0 && down < total) {
        const amt = ((total - down) / count).toFixed(2);
        prev.classList.remove('d-none');
        prev.innerHTML = `Per month: <strong>${parseFloat(amt).toLocaleString('en-US',{minimumFractionDigits:2})} ৳</strong> &times; ${count} Installment`;
    } else { prev.classList.add('d-none'); }
}
function submitPlan(e) {
    e.preventDefault();
    const data = {
        customer_name:      document.getElementById('pName').value.trim(),
        total_amount:       document.getElementById('pTotal').value,
        down_payment:       document.getElementById('pDown').value,
        installment_count:  document.getElementById('pCount').value,
        start_date:         document.getElementById('pStart').value,
        note:               document.getElementById('pNote').value.trim(),
    };
    const btn = document.getElementById('planSaveBtn');
    btn.disabled = true;
    fetch(BASE_URL+'/api/add_installment_plan.php',{method:'POST',body:new URLSearchParams(data)})
        .then(r=>r.json()).then(res=>{
            btn.disabled=false;
            if (res.success) { planModal.hide(); showToast(res.message,'success'); loadPlans(); document.getElementById('planForm').reset(); document.getElementById('installPreview').classList.add('d-none'); }
            else showToast(res.message,'danger');
        }).catch(()=>{ btn.disabled=false; showToast('Something went wrong.','danger'); });
}

// ── Events ────────────────────────────────────────────────────────────────────
document.getElementById('btnNewPlan')?.addEventListener('click', () => planModal.show());
document.querySelectorAll('#statusFilter button').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('#statusFilter button').forEach(b=>{
            b.className='btn btn-outline-'+(b.dataset.status===''?'danger':b.dataset.status==='active'?'primary':b.dataset.status==='completed'?'success':'secondary');
        });
        this.classList.remove('btn-outline-danger','btn-outline-primary','btn-outline-success','btn-outline-secondary');
        this.classList.add('active','btn-'+(this.dataset.status===''?'danger':this.dataset.status==='active'?'primary':this.dataset.status==='completed'?'success':'secondary'));
        activeStatus = this.dataset.status; loadPlans();
    });
});
document.getElementById('searchInput').addEventListener('input',function(){ clearTimeout(searchTimer); searchTimer=setTimeout(loadPlans,350); });

loadPlans();
