// ── DATA from PHP ──
const DASHBOARD_DATA = window.DASHBOARD_DATA || {};
const LISTS_DATA = DASHBOARD_DATA.lists || {};
const LIST_LABELS = DASHBOARD_DATA.labels || {};
const LIST_META = DASHBOARD_DATA.meta || {};
const CAT_COLORS = DASHBOARD_DATA.colors || {};
const CAT_ICONS = DASHBOARD_DATA.icons || {};
const PRIO_LABELS = { tinggi:'↑ Tinggi', sedang:'→ Sedang', rendah:'↓ Rendah' };
const PRIO_COLORS = { tinggi:'#c10000', sedang:'#b87200', rendah:'#006b38' };

// ── NAVIGATION ──
function showPage(id, navEl) {
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    document.getElementById('page-' + id).classList.add('active');
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    if (navEl) navEl.classList.add('active');
    const titles = { dashboard:'Dashboard', lists:'Daftar Tugas', calendar:'Kalender', detail:'Detail List' };
    const subtitles = {
        dashboard:'Ringkasan seluruh aktivitas',
        lists:'Ringkasan seluruh aktivitas',
        calendar:'Tugas dari semua list berdasarkan deadline',
        detail:'Detail list tugas'
    };
    document.getElementById('topbarTitle').textContent = titles[id] || id;
    const sub = document.querySelector('.topbar-subtitle');
    if (sub) sub.textContent = subtitles[id] || subtitles.lists;
    if (id === 'calendar' && typeof window.renderCalendar === 'function') {
        window.renderCalendar();
    }
}

function showListDetail(kat) {
    const items = LISTS_DATA[kat] || [];
    const color = CAT_COLORS[kat] || '#b87200';
    const icon  = CAT_ICONS[kat]  || '◉';
    const done  = items.filter(r => r.status_tugas === 'Selesai').length;

    const label = LIST_LABELS[kat] || ucfirst(kat);
    const meta = LIST_META[kat] || {};
    const owner = meta.owner_username && Number(meta.is_owner) !== 1 ? ` <span style="font-family:'Anybody',sans-serif;font-size:11px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;opacity:.35;">@${escHtml(meta.owner_username)}</span>` : '';
    const jenis = meta.jenis === 'kelompok' ? 'Kelompok' : 'Pribadi';
    document.getElementById('detail-title').innerHTML = icon + ' <em style="font-family:\'DM Serif Display\',serif;font-style:italic;color:' + color + '">' + escHtml(label) + '</em>' + owner;
    document.getElementById('detail-super').textContent = jenis + ' List';
    document.getElementById('detail-add-kat').value = 'list:' + kat;
    syncDetailTools(kat, label, meta);

    const tbody = document.getElementById('detail-tbody');
    if (!items.length) {
        tbody.innerHTML = '<tr><td colspan="6"><div class="empty-state"><div class="empty-icon">◎</div>Belum ada tugas di list ini</div></td></tr>';
    } else {
        tbody.innerHTML = items.map((r, i) => {
            const isDone = r.status_tugas === 'Selesai';
            const prioHtml = r.prioritas
                ? `<span class="prio-badge ${r.prioritas}">${PRIO_LABELS[r.prioritas]||r.prioritas}</span>`
                : '<span style="opacity:.3">—</span>';
            const dueHtml = r.due_date
                ? `<span style="font-size:11px;opacity:.5">${formatDate(r.due_date)}</span>`
                : '<span style="opacity:.3">—</span>';
            const statusHtml = `<span class="status-pill ${isDone?'done':'pending'}">${isDone?'✓ Selesai':'○ Pending'}</span>`;
            const actHtml = isDone
                ? `<a href="#" data-task-id="${r.id}" class="action-btn del-btn">✕</a>`
                : `<a href="#" data-task-id="${r.id}" class="action-btn done-btn">✓</a>
                   <a href="#" data-task-id="${r.id}" class="action-btn del-btn">✕</a>`;
            return `<tr class="${isDone?'row-done':''}">
                <td style="opacity:.3;font-weight:800">${String(i+1).padStart(2,'0')}</td>
                <td class="task-name-cell" style="font-weight:700">${escHtml(r.nama_tugas)}</td>
                <td>${prioHtml}</td>
                <td>${dueHtml}</td>
                <td>${statusHtml}</td>
                <td style="display:flex;gap:6px;flex-wrap:wrap">${actHtml}</td>
            </tr>`;
        }).join('');
    }

    document.getElementById('topbarTitle').textContent = label;
    showPage('detail', null);
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    document.getElementById('nav-lists').classList.add('active');
}

function formatDate(s) {
    if (!s) return '';
    const parts = String(s).slice(0, 10).split('-').map(Number);
    const dt = new Date(parts[0], parts[1] - 1, parts[2]);
    return dt.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
}

function escHtml(s) {
    s = String(s ?? '');
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function ucfirst(s) { return s.charAt(0).toUpperCase() + s.slice(1); }

function syncDetailTools(kat, label, meta) {
    const tools = document.getElementById('detailTools');
    const editId = document.getElementById('edit-list-id');
    const deleteId = document.getElementById('delete-list-id');
    const editName = document.getElementById('edit-list-name');
    const editType = document.getElementById('edit-list-type');
    const editMembers = document.getElementById('edit-list-members');
    const deleteForm = document.getElementById('deleteListForm');
    const isOwner = Number(meta.is_owner || 0) === 1;
    const isDefaultList = meta.slug === 'pribadi';

    tools.classList.toggle('is-visible', isOwner && !isDefaultList);
    if (!isOwner || isDefaultList) return;

    editId.value = kat;
    deleteId.value = kat;
    editName.value = label;
    editType.value = meta.jenis === 'kelompok' ? 'kelompok' : 'pribadi';
    editMembers.value = meta.member_usernames || '';
    tools.classList.toggle('is-group', editType.value === 'kelompok');
    editMembers.required = editType.value === 'kelompok';
    deleteForm.style.display = 'flex';
}

const editListType = document.getElementById('edit-list-type');
const editListMembers = document.getElementById('edit-list-members');
if (editListType && editListMembers) {
    const syncEditListType = () => {
        const tools = document.getElementById('detailTools');
        const isGroup = editListType.value === 'kelompok';
        tools.classList.toggle('is-group', isGroup);
        editListMembers.required = isGroup;
    };
    editListType.addEventListener('change', syncEditListType);
}

const listJenis = document.getElementById('listJenis');
const listMembers = document.getElementById('listMembers');
if (listJenis && listMembers) {
    const syncListType = () => {
        const isGroup = listJenis.value === 'kelompok';
        listJenis.closest('.modal-form').classList.toggle('is-group', isGroup);
        listMembers.required = isGroup;
    };
    listJenis.addEventListener('change', syncListType);
    syncListType();
}

// ── TOAST ──
const addListModal = document.getElementById('addListModal');
const openAddListModal = document.getElementById('openAddListModal');
const closeAddListModal = document.getElementById('closeAddListModal');
const cancelAddListModal = document.getElementById('cancelAddListModal');

function setAddListModal(open) {
    addListModal.classList.toggle('is-open', open);
    addListModal.setAttribute('aria-hidden', open ? 'false' : 'true');
    if (open) {
        const nameInput = addListModal.querySelector('input[name="nama_list"]');
        if (nameInput) nameInput.focus();
    }
}

if (addListModal && openAddListModal) {
    openAddListModal.addEventListener('click', () => setAddListModal(true));
    closeAddListModal.addEventListener('click', () => setAddListModal(false));
    cancelAddListModal.addEventListener('click', () => setAddListModal(false));
    addListModal.addEventListener('click', (event) => {
        if (event.target === addListModal) setAddListModal(false);
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && addListModal.classList.contains('is-open')) {
            setAddListModal(false);
        }
    });
}

function showToast(msg, type = 'ok') {
    const c = document.getElementById('toastContainer');
    const t = document.createElement('div');
    t.className = 'toast' + (type === 'error' ? ' error' : '');
    t.textContent = msg;
    c.appendChild(t);
    setTimeout(() => t.remove(), 3300);
}

if (DASHBOARD_DATA.toast) {
    document.addEventListener('DOMContentLoaded', () => {
        showToast(DASHBOARD_DATA.toast.msg, DASHBOARD_DATA.toast.type || 'ok');
    });
}

window.addEventListener('load', () => {
    document.querySelectorAll('.kpi-ring-fill').forEach(el => {
        const pct = parseFloat(el.dataset.pct || 0);
        el.style.strokeDashoffset = 188 - (1.88 * pct);
    });
});

// ── AJAX task actions (no full reload) ──
async function ajaxCompleteTask(id) {
    await LumitaskApi.completeTask(id);
    showToast('Tugas diselesaikan');
    location.reload();
}

async function ajaxDeleteTask(id) {
    if (!confirm('Hapus tugas ini?')) return;
    await LumitaskApi.deleteTask(id);
    showToast('Tugas dihapus');
    location.reload();
}

document.addEventListener('click', (e) => {
    const done = e.target.closest('a.done-btn[data-task-id]');
    const del = e.target.closest('a.del-btn[data-task-id]');
    if (done) {
        e.preventDefault();
        ajaxCompleteTask(done.dataset.taskId).catch(err => showToast(err.message, 'error'));
    }
    if (del) {
        e.preventDefault();
        ajaxDeleteTask(del.dataset.taskId).catch(err => showToast(err.message, 'error'));
    }
});

const detailAddForm = document.getElementById('detail-add-form');
if (detailAddForm && detailAddForm.dataset.ajax === '1') {
    detailAddForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(detailAddForm);
        try {
            await LumitaskApi.createTask(Object.fromEntries(fd.entries()));
            showToast('Tugas ditambahkan');
            location.reload();
        } catch (err) {
            showToast(err.message || 'Gagal menambah tugas', 'error');
        }
    });
}

// Collaboration polling (near-realtime)
let lastPoll = new Date().toISOString().slice(0, 19).replace('T', ' ');
setInterval(async () => {
    if (typeof LumitaskApi === 'undefined') return;
    try {
        const res = await LumitaskApi.pollCollaboration(lastPoll);
        lastPoll = res.server_time || lastPoll;
        if (res.activities && res.activities.length) {
            const n = document.getElementById('notifCount');
            if (n) n.textContent = String(parseInt(n.textContent || '0', 10) + res.activities.length);
        }
    } catch (_) { /* silent */ }
}, 12000);

const notifBell = document.getElementById('notifBell');
if (notifBell) {
    notifBell.addEventListener('click', async () => {
        try {
            const res = await LumitaskApi.getNotifications();
            const items = (res.notifications || []).slice(0, 5).map(n => n.title).join('\n') || 'Tidak ada notifikasi baru';
            alert(items);
            await LumitaskApi.markNotificationsRead();
            document.getElementById('notifCount').textContent = '0';
        } catch (err) {
            showToast(err.message, 'error');
        }
    });
}
