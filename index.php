<?php

$app = require __DIR__ . '/bootstrap/app.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Repositories\TaskListRepository;
use App\Repositories\TaskRepository;
use App\Repositories\NotificationRepository;

Auth::requireLogin();

$user_id = Auth::id();
$taskRepo = new TaskRepository();
$listRepo = new TaskListRepository();

$all_rows = $taskRepo->allForUser($user_id);
$list_rows = $listRepo->listsForUser($user_id);
$list_meta = [];
$lists = [];
foreach ($list_rows as $list) {
    $key = (string) $list['id'];
    $list_meta[$key] = $list;
    $lists[$key] = [];
}

(new NotificationRepository())->syncDeadlineReminders($user_id);
$notifications = (new NotificationRepository())->forUser($user_id, true);

$total = count($all_rows);
$selesai = count(array_filter($all_rows, fn ($r) => $r['status_tugas'] === 'Selesai'));
$belum = $total - $selesai;
$pct = $total > 0 ? round(($selesai / $total) * 100) : 0;

// Group by list
$by_cat = [];
foreach ($lists as $key => $_) {
    $by_cat[$key] = ['total' => 0, 'selesai' => 0];
}
foreach ($all_rows as $r) {
    $k = (string) ($r['accessible_list_id'] ?? $r['list_id'] ?? $r['kategori'] ?? 'pribadi');
    if (!isset($by_cat[$k])) {
        $by_cat[$k] = ['total' => 0, 'selesai' => 0];
    }
    $by_cat[$k]['total'] = ($by_cat[$k]['total'] ?? 0) + 1;
    $by_cat[$k]['selesai'] = ($by_cat[$k]['selesai'] ?? 0) + ($r['status_tugas'] === 'Selesai' ? 1 : 0);
}

// Group by priority
$by_prio = ['tinggi' => ['total' => 0, 'selesai' => 0], 'sedang' => ['total' => 0, 'selesai' => 0], 'rendah' => ['total' => 0, 'selesai' => 0]];
foreach ($all_rows as $r) {
    $p = $r['prioritas'] ?: null;
    if ($p && isset($by_prio[$p])) {
        ++$by_prio[$p]['total'];
        if ($r['status_tugas'] === 'Selesai') {
            ++$by_prio[$p]['selesai'];
        }
    }
}

// Overdue count
$overdue = 0;
foreach ($all_rows as $r) {
    if (!empty($r['due_date']) && $r['status_tugas'] !== 'Selesai') {
        if (strtotime($r['due_date']) < strtotime('today')) {
            ++$overdue;
        }
    }
}

// Upcoming (within 3 days, not done)
$upcoming = [];
foreach ($all_rows as $r) {
    if (!empty($r['due_date']) && $r['status_tugas'] !== 'Selesai') {
        $diff = strtotime($r['due_date']) - strtotime('today');
        if ($diff >= 0 && $diff <= 86400 * 3) {
            $upcoming[] = $r;
        }
    }
}

// Recent tasks (last 5)
$recent = array_slice(array_reverse($all_rows), 0, 5);

$category_colors = [
    'pelajaran' => '#3366ff',
    'proyek' => '#c1006b',
    'organisasi' => '#6b00c1',
    'pribadi' => '#008b8b',
    'lainnya' => '#b87200',
];
$category_icons = [
    'pelajaran' => '📚',
    'proyek' => '🗂',
    'organisasi' => '🏛',
    'pribadi' => '✦',
    'lainnya' => '◉',
];

foreach ($all_rows as $r) {
    $k = (string) ($r['accessible_list_id'] ?? $r['list_id'] ?? $r['kategori'] ?? 'pribadi');
    if (!isset($lists[$k])) {
        $slug = $r['kategori'] ?: 'pribadi';
        $lists[$k] = [];
        $list_meta[$k] = [
            'id' => $k,
            'nama_list' => $r['nama_list'] ?? ucfirst($slug),
            'slug' => $slug,
            'jenis' => $r['list_jenis'] ?? 'pribadi',
            'warna' => $r['list_warna'] ?? ($category_colors[$slug] ?? '#b87200'),
            'ikon' => $r['list_ikon'] ?? ($category_icons[$slug] ?? '.'),
            'owner_username' => $r['owner_username'] ?? '',
            'is_owner' => $r['is_owner'] ?? 1,
        ];
    }
    $lists[$k][] = $r;
}

foreach ($list_meta as $key => $meta) {
    $category_colors[$key] = $meta['warna'] ?: '#b87200';
    $category_icons[$key] = $meta['ikon'] ?: '.';
}

$toast = \App\Core\Session::pullFlash('toast');

$calendar_tasks = array_map(function ($r) {
    $k = (string) ($r['accessible_list_id'] ?? $r['list_id'] ?? $r['kategori'] ?? 'pribadi');

    return [
        'id' => $r['id'],
        'nama_tugas' => $r['nama_tugas'],
        'due_date' => $r['due_date'] ?: null,
        'status_tugas' => $r['status_tugas'],
        'prioritas' => $r['prioritas'] ?: null,
        'list_key' => $k,
    ];
}, $all_rows);

$assetVer = max(
    filemtime(__DIR__.'/css/dashboard.css') ?: 0,
    filemtime(__DIR__.'/js/dashboard.js') ?: 0,
    filemtime(__DIR__.'/js/calendar.js') ?: 0,
    filemtime(__DIR__.'/js/api.js') ?: 0
);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard — Luminous</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Anybody:wght@300;400;600;700;800;900&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/dashboard.css?v=<?php echo $assetVer; ?>">
</head>
<body>

<!-- ═══ SIDENAV ═══ -->
<nav class="sidenav">
    <div class="logo-tag">✦ Luminous · Vol. <?php echo date('Y'); ?></div>
    <div class="logo">To do<br><em>list</em></div>
    <div class="logo-sub">Organisasi · <?php echo date('d M Y'); ?></div>

    <div class="nav-section-label">Menu</div>
    <button class="nav-item active" onclick="showPage('lists',this)" id="nav-lists">
        <span class="icon">◉</span> Daftar Tugas
        <span class="badge"><?php echo $total; ?></span>
    </button>
    <button class="nav-item" onclick="showPage('calendar',this)" id="nav-calendar">
        <span class="icon">▦</span> Kalender
    </button>
    <div class="nav-section-label">List</div>
    <?php foreach ($lists as $kat => $items) {
        $color = $category_colors[$kat] ?? '#b87200';
        $icon = $category_icons[$kat] ?? '◉';
        $done_k = count(array_filter($items, fn ($r) => $r['status_tugas'] === 'Selesai'));
        ?>
    <button class="nav-list-item" onclick="showListDetail('<?php echo htmlspecialchars($kat); ?>')" style="color:rgba(250,246,238,.38)">
        <span class="list-dot" style="background:<?php echo $color; ?>"></span>
        <?php echo htmlspecialchars($list_meta[$kat]['nama_list'] ?? ucfirst($kat)); ?>
        <span class="nav-list-cnt"><?php echo count($items); ?></span>
    </button>
    <?php } ?>

    <div class="sidenav-spacer"></div>

    <div class="sidenav-footer">
        <div class="user-row">
            <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?></div>
            <div>
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></div>
                <div class="user-role">Member</div>
            </div>
        </div>
        <a href="logout.php" onclick="return confirm('Yakin logout?')" class="btn-logout">⎋ &nbsp;Logout</a>
    </div>
</nav>

<!-- ═══ CONTENT ═══ -->
<div class="content">

    <!-- TOPBAR -->
    <div class="topbar">
        <div>
            <div class="topbar-title" id="topbarTitle">Daftar Tugas</div>
            <div class="topbar-subtitle">Ringkasan seluruh aktivitas</div>
        </div>
        <div class="topbar-right">
            <div class="notif-bell" id="notifBell" title="Notifikasi">🔔 <span class="notif-count" id="notifCount"><?php echo count($notifications); ?></span></div>
            <?php if (Auth::isAdmin()) { ?><span class="role-badge">Admin</span><?php } ?>
            <div class="topbar-date"><?php echo date('l, d F Y'); ?></div>
        </div>
    </div>

    <!-- PAGES -->
    <div class="pages">

        <!-- ─── DASHBOARD ─── -->
        <div class="page" id="page-dashboard">

            <div class="dash-hero">
                <div>
                    <div style="font-size:9px;font-weight:800;letter-spacing:0.28em;text-transform:uppercase;opacity:.3;margin-bottom:8px;">✦ Overview</div>
                    <div class="dash-welcome">Selamat datang,<br><em><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Kawan'); ?></em></div>
                </div>
                <div class="dash-date-block">
                    <div class="dash-day"><?php echo date('d'); ?></div>
                    <div style="font-size:10px;font-weight:700;letter-spacing:0.14em;text-transform:uppercase;opacity:.3;text-align:right;"><?php echo date('M Y'); ?></div>
                </div>
            </div>

            <!-- KPI -->
            <div class="kpi-row">
                <div class="kpi-card">
                    <div class="kpi-band" style="background:var(--deco2)"></div>
                    <div class="kpi-label">Total Tugas</div>
                    <div class="kpi-val" style="color:var(--deco2)"><?php echo $total; ?></div>
                    <div class="kpi-sub">Semua list</div>
                    <div class="kpi-icon">◉</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-band" style="background:var(--deco3)"></div>
                    <div class="kpi-label">Selesai</div>
                    <div class="kpi-val" style="color:var(--deco3)"><?php echo $selesai; ?></div>
                    <div class="kpi-sub">Tugas selesai</div>
                    <div class="kpi-icon">✓</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-band" style="background:var(--deco1)"></div>
                    <div class="kpi-label">Pending</div>
                    <div class="kpi-val" style="color:var(--deco1)"><?php echo $belum; ?></div>
                    <div class="kpi-sub">Belum selesai</div>
                    <div class="kpi-icon">○</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-band" style="background:<?php echo $overdue > 0 ? '#c10000' : '#006b38'; ?>"></div>
                    <div class="kpi-label">Terlambat</div>
                    <div class="kpi-val" style="color:<?php echo $overdue > 0 ? '#c10000' : '#006b38'; ?>"><?php echo $overdue; ?></div>
                    <div class="kpi-sub">Melewati deadline</div>
                    <div class="kpi-icon">⚠</div>
                </div>
            </div>

            <!-- Mid row -->
            <div class="mid-row">

                <!-- Category breakdown -->
                <div class="panel">
                    <div class="panel-head">
                        <div class="panel-title">✦ Per List</div>
                        <button class="panel-head-link" onclick="showPage('lists',document.getElementById('nav-lists'))">Lihat Semua →</button>
                    </div>
                    <div class="cat-list">
                        <?php foreach ($by_cat as $kat => $info) {
                            $color = $category_colors[$kat] ?? '#b87200';
                            $icon = $category_icons[$kat] ?? '◉';
                            $pct_k = $info['total'] > 0 ? round($info['selesai'] / $info['total'] * 100) : 0;
                            ?>
                        <div class="cat-row">
                            <div class="cat-row-top">
                                <div class="cat-name">
                                    <span><?php echo $icon; ?></span>
                                    <span style="text-transform:capitalize;"><?php echo htmlspecialchars($list_meta[$kat]['nama_list'] ?? ucfirst($kat)); ?></span>
                                </div>
                                <div class="cat-count"><?php echo $info['selesai']; ?>/<?php echo $info['total']; ?> · <?php echo $pct_k; ?>%</div>
                            </div>
                            <div class="cat-bar-track">
                                <div class="cat-bar-fill" style="width:<?php echo $pct_k; ?>%;background:<?php echo $color; ?>;"></div>
                            </div>
                        </div>
                        <?php } ?>
                        <?php if (empty($by_cat)) { ?>
                        <div class="empty-state"><div class="empty-icon">◎</div>Belum ada data</div>
                        <?php } ?>
                    </div>
                </div>

                <!-- Priority & upcoming -->
                <div style="display:flex;flex-direction:column;gap:20px;">

                    <!-- Priority -->
                    <div class="panel">
                        <div class="panel-head">
                            <div class="panel-title">✦ Per Prioritas</div>
                        </div>
                        <div class="prio-list">
                            <?php
                                $prio_cfg = [
                                    'tinggi' => ['label' => '↑ Tinggi', 'color' => '#c10000'],
                                    'sedang' => ['label' => '→ Sedang', 'color' => '#b87200'],
                                    'rendah' => ['label' => '↓ Rendah', 'color' => '#006b38'],
                                ];
foreach ($prio_cfg as $pk => $pc) {
    $pi = $by_prio[$pk];
    $pct_p = $pi['total'] > 0 ? round($pi['selesai'] / $pi['total'] * 100) : 0;
    ?>
                            <div class="prio-row">
                                <div class="prio-bar" style="background:<?php echo $pc['color']; ?>;width:<?php echo $pct_p; ?>%;"></div>
                                <div class="prio-dot" style="background:<?php echo $pc['color']; ?>"></div>
                                <div class="prio-name"><?php echo $pc['label']; ?></div>
                                <div class="prio-stat" style="color:<?php echo $pc['color']; ?>"><?php echo $pi['selesai']; ?>/<?php echo $pi['total']; ?></div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>

                    <!-- Upcoming -->
                    <div class="panel">
                        <div class="panel-head">
                            <div class="panel-title">⏳ Segera Jatuh Tempo</div>
                        </div>
                        <?php if (empty($upcoming)) { ?>
                        <div class="empty-state"><div class="empty-icon">✦</div>Tidak ada deadline dekat</div>
                        <?php } else { ?>
                        <div class="upcoming-list">
                            <?php foreach ($upcoming as $u) {
                                $diff = strtotime($u['due_date']) - strtotime('today');
                                $dclass = $diff === 0 ? 'urgent' : 'soon';
                                $dlabel = $diff === 0 ? 'Hari ini' : 'Besok';
                                $ukey = (string) ($u['accessible_list_id'] ?? $u['list_id'] ?? $u['kategori'] ?? '');
                                $color = $category_colors[$ukey] ?? '#b87200';
                                ?>
                            <div class="upcoming-item">
                                <div class="upcoming-dot" style="background:<?php echo $color; ?>"></div>
                                <div class="upcoming-name"><?php echo htmlspecialchars($u['nama_tugas']); ?></div>
                                <span class="upcoming-due <?php echo $dclass; ?>"><?php echo $dlabel; ?></span>
                            </div>
                            <?php } ?>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <!-- Recent tasks -->
            <div class="panel">
                <div class="panel-head">
                    <div class="panel-title">◉ Tugas Terbaru</div>
                    <button class="panel-head-link" onclick="showPage('lists',document.getElementById('nav-lists'))">Lihat Semua →</button>
                </div>
                <?php if (empty($recent)) { ?>
                <div class="empty-state"><div class="empty-icon">◎</div>Belum ada tugas</div>
                <?php } else { ?>
                <table class="recent-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama Tugas</th>
                            <th>List</th>
                            <th>Prioritas</th>
                            <th>Status</th>
                            <th>Deadline</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recent as $i => $r) {
                        $done_r = $r['status_tugas'] === 'Selesai';
                        $kat_r = (string) ($r['accessible_list_id'] ?? $r['list_id'] ?? $r['kategori'] ?? '');
                        $color_r = $category_colors[$kat_r] ?? '#b87200';
                        $prio_r = $r['prioritas'] ?? '';
                        ?>
                    <tr>
                        <td style="opacity:.3;font-weight:800;"><?php echo str_pad($i + 1, 2, '0', STR_PAD_LEFT); ?></td>
                        <td style="font-weight:700;<?php echo $done_r ? 'text-decoration:line-through;opacity:.4;' : ''; ?>"><?php echo htmlspecialchars($r['nama_tugas']); ?></td>
                        <td>
                            <?php if ($kat_r) { ?>
                            <span style="color:<?php echo $color_r; ?>;font-size:10px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;"><?php echo htmlspecialchars($list_meta[$kat_r]['nama_list'] ?? ucfirst($kat_r)); ?></span>
                            <?php } else { ?><span style="opacity:.3;">—</span><?php } ?>
                        </td>
                        <td>
                            <?php if ($prio_r) { ?>
                            <span class="prio-badge <?php echo $prio_r; ?>"><?php echo ucfirst($prio_r); ?></span>
                            <?php } else { ?>—<?php } ?>
                        </td>
                        <td>
                            <span class="status-pill <?php echo $done_r ? 'done' : 'pending'; ?>">
                                <?php echo $done_r ? '✓ Selesai' : '○ Pending'; ?>
                            </span>
                        </td>
                        <td style="font-size:11px;opacity:.5;">
                            <?php echo !empty($r['due_date']) ? date('d M Y', strtotime($r['due_date'])) : '—'; ?>
                        </td>
                    </tr>
                    <?php } ?>
                    </tbody>
                </table>
                <?php } ?>
            </div>

        </div><!-- /page-dashboard -->


        <!-- ─── LISTS PAGE ─── -->
        <div class="page active" id="page-lists">
            <div class="lists-header">
                <div>
                    <div style="font-size:9px;font-weight:800;letter-spacing:.28em;text-transform:uppercase;opacity:.3;margin-bottom:8px;">✦ Daftar Tugas</div>
                    <div class="lists-heading">Pilih <em>List</em></div>
                </div>
                <button class="add-list-btn" type="button" id="openAddListModal" style="align-self:flex-end;">+ Tambah List</button>
            </div>

            <div class="lists-grid">
                <?php foreach ($lists as $kat => $items) {
                    $color = $category_colors[$kat] ?? '#b87200';
                    $icon = $category_icons[$kat] ?? '◉';
                    $done_k = count(array_filter($items, fn ($r) => $r['status_tugas'] === 'Selesai'));
                    $pct_k = count($items) > 0 ? round($done_k / count($items) * 100) : 0;
                    $delay = array_search($kat, array_keys($lists)) * 0.06;
                    ?>
                <div class="list-card" onclick="showListDetail('<?php echo htmlspecialchars($kat); ?>')" style="animation-delay:<?php echo $delay; ?>s">
                    <div class="list-card-band" style="background:<?php echo $color; ?>"></div>
                    <div class="list-card-inner">
                        <div class="list-card-icon"><?php echo $icon; ?></div>
                        <div class="list-card-name"><?php echo htmlspecialchars($list_meta[$kat]['nama_list'] ?? ucfirst($kat)); ?></div>
                        <div class="list-card-count"><?php echo count($items); ?> tugas &nbsp;·&nbsp; <?php echo $pct_k; ?>% selesai</div>
                        <div class="list-card-prog-track">
                            <div class="list-card-prog-fill" style="width:<?php echo $pct_k; ?>%;background:<?php echo $color; ?>"></div>
                        </div>
                        <div class="list-card-stats">
                            <span style="color:var(--deco3)">✓ <?php echo $done_k; ?></span>
                            <span style="color:var(--deco1)">○ <?php echo count($items) - $done_k; ?></span>
                        </div>
                    </div>
                    <div class="list-card-footer">
                        <span>Lihat detail</span>
                        <span>→</span>
                    </div>
                </div>
                <?php } ?>
                <?php if (empty($lists)) { ?>
                <div class="empty-state" style="grid-column:1/-1;">
                    <div class="empty-icon">◎</div>
                    Belum ada tugas — tambahkan dulu!
                </div>
                <?php } ?>
            </div>
        </div><!-- /page-lists -->


        <!-- ─── CALENDAR ─── -->
        <div class="page" id="page-calendar">
            <div class="lists-header">
                <div>
                    <div style="font-size:9px;font-weight:800;letter-spacing:.28em;text-transform:uppercase;opacity:.3;margin-bottom:8px;">✦ Kalender</div>
                    <div class="lists-heading">Semua <em>Tugas</em></div>
                </div>
            </div>

            <div class="cal-layout">
                <div class="cal-main panel">
                    <div class="cal-nav">
                        <button type="button" class="cal-nav-btn" id="cal-prev" aria-label="Bulan sebelumnya">←</button>
                        <div class="cal-month-label" id="cal-month-label">—</div>
                        <button type="button" class="cal-nav-btn" id="cal-next" aria-label="Bulan berikutnya">→</button>
                        <button type="button" class="cal-today-btn" id="cal-today">Hari ini</button>
                    </div>
                    <div class="cal-weekdays">
                        <div class="cal-weekday">Sen</div>
                        <div class="cal-weekday">Sel</div>
                        <div class="cal-weekday">Rab</div>
                        <div class="cal-weekday">Kam</div>
                        <div class="cal-weekday">Jum</div>
                        <div class="cal-weekday">Sab</div>
                        <div class="cal-weekday">Min</div>
                    </div>
                    <div class="cal-grid" id="cal-grid"></div>
                    <p class="cal-hint">Hanya tugas dengan deadline yang ditampilkan di kalender.</p>
                </div>
                <aside class="cal-sidebar panel" id="cal-sidebar">
                    <div class="cal-sidebar-head">
                        <div class="cal-sidebar-kicker" id="cal-sidebar-kicker">Pilih tanggal</div>
                        <div class="cal-sidebar-date" id="cal-sidebar-date">—</div>
                    </div>
                    <div class="cal-sidebar-body" id="cal-sidebar-body">
                        <div class="empty-state" style="padding:32px 12px;">
                            <div class="empty-icon">▦</div>
                            Klik tanggal di kalender untuk melihat tugas
                        </div>
                    </div>
                </aside>
            </div>
        </div><!-- /page-calendar -->


        <!-- ─── LIST DETAIL ─── -->
        <div class="page" id="page-detail">
            <button class="back-btn" onclick="showPage('lists',document.getElementById('nav-lists'))">← Kembali ke Daftar</button>

            <div style="margin-bottom:24px;">
                <div style="font-size:9px;font-weight:800;letter-spacing:.28em;text-transform:uppercase;opacity:.3;margin-bottom:6px;" id="detail-super">✦ List</div>
                <div class="lists-heading" id="detail-title">—</div>
                <div class="detail-tools" id="detailTools">
                    <form action="edit_list.php" method="POST" class="edit-list-form" id="editListForm">
                        <input type="hidden" name="list_id" id="edit-list-id" value="">
                        <input class="edit-field" type="text" name="nama_list" id="edit-list-name" placeholder="Nama list" required autocomplete="off">
                        <select class="edit-field" name="jenis" id="edit-list-type">
                            <option value="pribadi">Pribadi</option>
                            <option value="kelompok">Kelompok</option>
                        </select>
                        <input class="edit-field members" type="text" name="members" id="edit-list-members" placeholder="username anggota, pisahkan koma" autocomplete="off">
                        <button class="tool-btn" type="submit">Simpan List</button>
                    </form>
                    <form action="hapus_list.php" method="POST" class="delete-list-form" id="deleteListForm">
                        <input type="hidden" name="list_id" id="delete-list-id" value="">
                        <button class="tool-btn delete-list-btn" type="submit" onclick="return confirm('Hapus list ini beserta semua tugasnya?')">Hapus List</button>
                    </form>
                </div>
            </div>

            <div class="task-table-wrap">
                <table class="task-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama Tugas</th>
                            <th>Prioritas</th>
                            <th>Deadline</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="detail-tbody">
                    </tbody>
                </table>
                <form action="tambah.php" method="POST" class="add-task-row" id="detail-add-form" data-ajax="1">
                    <?php echo Csrf::field(); ?>
                    <input type="hidden" name="kategori" id="detail-add-kat" value="">
                    <input class="field field-name" type="text" name="nama_tugas" placeholder="Tambah tugas baru…" required autocomplete="off">
                    <input class="field field-sm" type="date" name="due_date" title="Deadline">
                    <select class="field field-sm" name="prioritas" style="font-family:'Anybody',sans-serif;font-size:12px;font-weight:700;cursor:pointer;color:var(--ink);background:transparent;border:1.5px solid rgba(26,18,8,.2);padding:8px 10px;outline:none;">
                        <option value="">Prioritas</option>
                        <option value="tinggi">↑ Tinggi</option>
                        <option value="sedang">→ Sedang</option>
                        <option value="rendah">↓ Rendah</option>
                    </select>
                    <button type="submit" class="add-btn">+ Tambah</button>
                </form>
            </div>
        </div><!-- /page-detail -->


    </div><!-- /pages -->
</div><!-- /content -->

<div class="modal-backdrop" id="addListModal" aria-hidden="true">
    <div class="modal-window" role="dialog" aria-modal="true" aria-labelledby="addListModalTitle">
        <div class="modal-head">
            <div>
                <div class="modal-kicker">Tambah List</div>
                <div class="modal-title" id="addListModalTitle">List <em>baru</em></div>
            </div>
            <button class="modal-close" type="button" id="closeAddListModal" aria-label="Tutup">×</button>
        </div>
        <form action="tambah_list.php" method="POST" class="modal-form" id="addListForm" data-ajax="1">
            <?php echo Csrf::field(); ?>
            <input class="modal-field" type="text" name="nama_list" placeholder="Nama list baru" required autocomplete="off">
            <select class="modal-field" name="jenis" id="listJenis" title="Jenis list">
                <option value="pribadi">Pribadi</option>
                <option value="kelompok">Kelompok</option>
            </select>
            <input class="modal-field modal-members" type="text" name="members" id="listMembers" placeholder="username anggota, pisahkan koma" autocomplete="off">
            <div class="modal-actions">
                <button class="add-list-btn modal-secondary" type="button" id="cancelAddListModal">Batal</button>
                <button class="add-list-btn" type="submit">Buat List</button>
            </div>
        </form>
    </div>
</div>

<div class="toast-container" id="toastContainer"></div>

<script>
window.LUMITASK_BASE = <?php echo json_encode($app->config('base_path', '/Lumitask')); ?>;
window.DASHBOARD_DATA = {
    lists: <?php echo json_encode($lists, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
    tasks: <?php echo json_encode($calendar_tasks, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
    labels: <?php echo json_encode(array_map(fn ($m) => $m['nama_list'] ?? ucfirst($m['slug'] ?? ''), $list_meta), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
    meta: <?php echo json_encode($list_meta, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
    colors: <?php echo json_encode($category_colors, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
    icons: <?php echo json_encode($category_icons, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
    csrf: <?php echo json_encode(Csrf::token()); ?>,
    role: <?php echo json_encode(Auth::user()['role'] ?? 'member'); ?>,
    notifications: <?php echo json_encode($notifications, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
    toast: <?php echo json_encode($toast); ?>
};
</script>
<script src="js/api.js?v=<?php echo $assetVer; ?>"></script>
<script src="js/dashboard.js?v=<?php echo $assetVer; ?>"></script>
<script src="js/calendar.js?v=<?php echo $assetVer; ?>"></script>
</body>
</html>

