/**
 * Calendar view — all tasks across all lists (by due_date)
 */
(function () {
    const DATA = window.DASHBOARD_DATA || {};
    const TASKS = DATA.tasks || [];
    const LIST_LABELS = DATA.labels || {};
    const CAT_COLORS = DATA.colors || {};
    const CAT_ICONS = DATA.icons || {};
    const PRIO_LABELS = { tinggi: '↑ Tinggi', sedang: '→ Sedang', rendah: '↓ Rendah' };
    const MONTHS = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    let viewYear;
    let viewMonth;
    let selectedKey = null;
    let bound = false;

    function escHtml(s) {
        s = String(s ?? '');
        return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function ucfirst(s) {
        return s.charAt(0).toUpperCase() + s.slice(1);
    }

    function parseDateOnly(s) {
        const [y, m, d] = String(s).slice(0, 10).split('-').map(Number);
        return new Date(y, m - 1, d);
    }

    function dateKey(y, m, d) {
        return `${y}-${String(m + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
    }

    function tasksByDueDate() {
        const map = {};
        TASKS.forEach((t) => {
            if (!t.due_date) return;
            const key = String(t.due_date).slice(0, 10);
            if (!map[key]) map[key] = [];
            map[key].push(t);
        });
        return map;
    }

    function ensureView() {
        if (viewYear == null || viewMonth == null) {
            const now = new Date();
            viewYear = now.getFullYear();
            viewMonth = now.getMonth();
        }
    }

    function bindControls() {
        if (bound) return;
        const prev = document.getElementById('cal-prev');
        const next = document.getElementById('cal-next');
        const todayBtn = document.getElementById('cal-today');
        if (!prev || !next || !todayBtn) return;

        prev.addEventListener('click', () => shiftMonth(-1));
        next.addEventListener('click', () => shiftMonth(1));
        todayBtn.addEventListener('click', () => {
            const n = new Date();
            viewYear = n.getFullYear();
            viewMonth = n.getMonth();
            selectDay(n.getFullYear(), n.getMonth(), n.getDate());
            render();
        });
        bound = true;
    }

    function shiftMonth(delta) {
        ensureView();
        viewMonth += delta;
        if (viewMonth < 0) { viewMonth = 11; viewYear--; }
        if (viewMonth > 11) { viewMonth = 0; viewYear++; }
        render();
    }

    function selectDay(year, month, day) {
        selectedKey = dateKey(year, month, day);
        showSidebar(selectedKey, tasksByDueDate()[selectedKey] || []);
    }

    function showSidebar(key, tasks) {
        const kicker = document.getElementById('cal-sidebar-kicker');
        const dateEl = document.getElementById('cal-sidebar-date');
        const body = document.getElementById('cal-sidebar-body');
        if (!body) return;

        const d = parseDateOnly(key);
        if (kicker) kicker.textContent = tasks.length ? `${tasks.length} tugas` : 'Tidak ada tugas';
        if (dateEl) {
            dateEl.textContent = d.toLocaleDateString('id-ID', {
                weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
            });
        }

        if (!tasks.length) {
            body.innerHTML = [
                '<div class="empty-state" style="padding:32px 12px;">',
                '<div class="empty-icon">◎</div>',
                'Tidak ada tugas pada tanggal ini',
                '</div>'
            ].join('');
            return;
        }

        body.innerHTML = tasks.map((t) => {
            const isDone = t.status_tugas === 'Selesai';
            const color = CAT_COLORS[t.list_key] || '#b87200';
            const listLabel = LIST_LABELS[t.list_key] || ucfirst(t.list_key);
            const prioHtml = t.prioritas
                ? '<span class="prio-badge ' + t.prioritas + '">' + (PRIO_LABELS[t.prioritas] || t.prioritas) + '</span>'
                : '';
            const actHtml = isDone
                ? '<a href="hapus.php?id=' + t.id + '" class="action-btn del-btn" onclick="return confirm(\'Hapus?\')">✕</a>'
                : '<a href="selesai.php?id=' + t.id + '" class="action-btn done-btn">✓</a>' +
                  '<a href="hapus.php?id=' + t.id + '" class="action-btn del-btn" onclick="return confirm(\'Hapus?\')">✕</a>';
            return [
                '<article class="cal-task-card' + (isDone ? ' is-done' : '') + '">',
                '<div class="cal-task-list" style="color:' + color + '">' + escHtml(CAT_ICONS[t.list_key] || '◉') + ' ' + escHtml(listLabel) + '</div>',
                '<div class="cal-task-name">' + escHtml(t.nama_tugas) + '</div>',
                '<div class="cal-task-meta">' + prioHtml +
                '<span class="status-pill ' + (isDone ? 'done' : 'pending') + '">' + (isDone ? '✓ Selesai' : '○ Pending') + '</span></div>',
                '<div class="cal-task-actions">' + actHtml + '</div>',
                '</article>'
            ].join('');
        }).join('');
    }

    function render() {
        const grid = document.getElementById('cal-grid');
        const label = document.getElementById('cal-month-label');
        if (!grid || !label) return;

        ensureView();
        bindControls();

        label.textContent = MONTHS[viewMonth] + ' ' + viewYear;
        const byDate = tasksByDueDate();
        const today = new Date();
        const todayKey = dateKey(today.getFullYear(), today.getMonth(), today.getDate());

        const first = new Date(viewYear, viewMonth, 1);
        const startOffset = (first.getDay() + 6) % 7;
        const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();
        const parts = [];

        for (let i = 0; i < startOffset; i++) {
            parts.push('<div class="cal-cell cal-cell-empty" aria-hidden="true"></div>');
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const key = dateKey(viewYear, viewMonth, day);
            const tasks = byDate[key] || [];
            const isToday = key === todayKey;
            const isSelected = key === selectedKey;
            const pending = tasks.filter((t) => t.status_tugas !== 'Selesai').length;
            const chips = tasks.slice(0, 3).map((t) => {
                const color = CAT_COLORS[t.list_key] || '#b87200';
                const done = t.status_tugas === 'Selesai';
                return '<span class="cal-chip' + (done ? ' is-done' : '') + '" style="--chip-color:' + color + '" title="' + escHtml(t.nama_tugas) + '">' + escHtml(t.nama_tugas) + '</span>';
            }).join('');
            const more = tasks.length > 3
                ? '<span class="cal-more">+' + (tasks.length - 3) + ' lagi</span>'
                : '';

            parts.push(
                '<button type="button" class="cal-cell' +
                (isToday ? ' is-today' : '') +
                (isSelected ? ' is-selected' : '') +
                (tasks.length ? ' has-tasks' : '') +
                '" data-date="' + key + '">' +
                '<span class="cal-day-num">' + day + '</span>' +
                (pending ? '<span class="cal-day-badge">' + pending + '</span>' : '') +
                '<div class="cal-chips">' + chips + more + '</div>' +
                '</button>'
            );
        }

        grid.innerHTML = parts.join('');

        grid.querySelectorAll('.cal-cell[data-date]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const partsDate = btn.dataset.date.split('-').map(Number);
                selectDay(partsDate[0], partsDate[1] - 1, partsDate[2]);
                render();
            });
        });

        if (selectedKey && !grid.querySelector('.cal-cell.is-selected')) {
            showSidebar(selectedKey, byDate[selectedKey] || []);
        }
    }

    function boot() {
        ensureView();
        bindControls();
        render();
    }

    window.renderCalendar = render;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
