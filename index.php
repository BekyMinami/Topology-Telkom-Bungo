<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
include 'koneksi.php';

// Fungsi ICMP Ping untuk Perangkat Aktif
function pingIP($ip) {
    if (empty($ip)) return 'Pasif';
    $os = PHP_OS_FAMILY;
    $cmd = ($os === 'Windows') ? "ping -n 1 -w 500 " . escapeshellarg($ip) : "ping -c 1 -W 1 " . escapeshellarg($ip);
    exec($cmd, $output, $status);
    return ($status === 0) ? 'Normal' : 'Gangguan';
}

// Fungsi Rekursif Status Perangkat Pasif (Parent Cascade)
function getStatusPerangkat($row, $koneksi) {
    if (!empty($row['ip_address'])) {
        return pingIP($row['ip_address']);
    }
    if (!empty($row['parent_id'])) {
        $parent_id = $row['parent_id'];
        $q_parent = mysqli_query($koneksi, "SELECT * FROM perangkat WHERE id='$parent_id'");
        if ($q_parent && ($parent_data = mysqli_fetch_assoc($q_parent))) {
            return getStatusPerangkat($parent_data, $koneksi);
        }
    }
    return 'Normal';
}

// Hitung Statistik Perangkat
$total_node = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM perangkat"));
$jml_router = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM perangkat WHERE jenis='Router'"));
$jml_olt    = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM perangkat WHERE jenis='OLT'"));
$jml_odc    = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM perangkat WHERE jenis='ODC'"));
$jml_odp    = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM perangkat WHERE jenis='ODP'"));

// Hitung Total ODP Gangguan (Dampak)
$odp_down = 0;
$q_all = mysqli_query($koneksi, "SELECT * FROM perangkat WHERE jenis='ODP'");
if($q_all){
    while($odp = mysqli_fetch_assoc($q_all)){
        if(getStatusPerangkat($odp, $koneksi) == 'Gangguan'){
            $odp_down++;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NMS Portal - PT Telkom Indonesia Wilayah Bungo</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --telkom-red: #cc0000;
            --telkom-dark: #111827;
            --bg-canvas: #f3f4f6;
            --card-surface: #ffffff;
            --text-primary: #1f2937;
            --text-secondary: #4b5563;
            --text-muted: #9ca3af;
            --border-subtle: #e5e7eb;
            --sidebar-bg: rgba(17, 24, 39, 0.85);
            --sidebar-header: rgba(11, 15, 25, 0.9);
            --sidebar-text: #d1d5db;
            --table-th-bg: #f9fafb;
            --table-hover: #f9fafb;
        }

        [data-theme="dark"] {
            --telkom-red: #ef4444;
            --telkom-dark: #0f172a;
            --bg-canvas: #0b0f19;
            --card-surface: #131d31;
            --text-primary: #f3f4f6;
            --text-secondary: #9ca3af;
            --text-muted: #6b7280;
            --border-subtle: #1e293b;
            --sidebar-bg: rgba(9, 13, 22, 0.85);
            --sidebar-header: rgba(5, 7, 11, 0.9);
            --sidebar-text: #9ca3af;
            --table-th-bg: #17233f;
            --table-hover: #17233f;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-canvas);
            color: var(--text-primary);
            margin: 0;
            display: flex;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .sidebar {
            width: 265px;
            background: var(--sidebar-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            color: #ffffff;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 1100;
            display: flex;
            flex-direction: column;
            transition: transform 0.25s ease-in-out;
            border-right: 1px solid rgba(255, 255, 255, 0.06);
        }

        body.sidebar-collapsed .sidebar { transform: translateX(-100%); }
        body.sidebar-collapsed .main-content { margin-left: 0; max-width: 100%; }

        .sidebar-header {
            padding: 20px;
            background: var(--sidebar-header);
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .close-sidebar-btn { background: none; border: none; font-size: 16px; cursor: pointer; color: var(--text-muted); display: none; }

        .sidebar-menu {
            padding: 16px 12px;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .menu-label {
            font-size: 10px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin: 16px 0 6px 10px;
        }
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            color: var(--sidebar-text);
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
            background: rgba(255, 255, 255, 0.02);
            width: 100%;
            text-align: left;
        }
        .sidebar-link:hover { 
            background: rgba(255, 255, 255, 0.08); 
            color: #ffffff; 
            backdrop-filter: blur(4px);
        }
        .sidebar-link.active { 
            background: var(--telkom-red); 
            color: #ffffff; 
            font-weight: 600; 
            box-shadow: 0 4px 12px rgba(204, 0, 0, 0.3);
        }

        .sidebar-footer {
            padding: 16px;
            background: var(--sidebar-header);
            border-top: 1px solid rgba(255, 255, 255, 0.06);
        }

        .main-content {
            margin-left: 265px;
            flex: 1;
            padding: 24px 32px;
            box-sizing: border-box;
            transition: margin-left 0.25s ease-in-out;
            max-width: calc(100% - 265px);
            width: 100%;
        }

        .top-navbar {
            background: var(--card-surface);
            padding: 14px 22px;
            border-radius: 8px;
            border: 1px solid var(--border-subtle);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            border-left: 4px solid var(--telkom-red);
        }
        .nav-left, .nav-right { display: flex; align-items: center; gap: 12px; }
        
        .burger-btn, .theme-toggle-btn {
            background: transparent;
            border: 1px solid var(--border-subtle);
            padding: 6px 10px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .burger-btn:hover, .theme-toggle-btn:hover { background: var(--border-subtle); }
        .page-title h3 { margin: 0; font-size: 16px; font-weight: 700; color: var(--text-primary); }

        .sidebar-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(17, 24, 39, 0.5);
            backdrop-filter: blur(2px);
            z-index: 1050;
            display: none;
        }
        .sidebar-overlay.active { display: block; }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        .card-stat {
            background: var(--card-surface);
            padding: 18px 20px;
            border-radius: 8px;
            border: 1px solid var(--border-subtle);
        }
        .card-stat small { display: block; color: var(--text-muted); font-size: 11px; font-weight: 600; text-transform: uppercase; margin-bottom: 6px; }
        .card-stat h3 { margin: 0; font-size: 22px; font-weight: 700; color: var(--text-primary); }

        .container-grid {
            display: grid;
            grid-template-columns: 2.8fr 1fr;
            gap: 16px;
            margin-bottom: 24px;
        }
        #map {
            width: 100%;
            height: 400px;
            border-radius: 8px;
            border: 1px solid var(--border-subtle);
        }
        .chart-box {
            background: var(--card-surface);
            padding: 20px;
            border-radius: 8px;
            border: 1px solid var(--border-subtle);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 400px;
            box-sizing: border-box;
        }
        .chart-box h4 { margin: 0 0 12px 0; font-size: 13px; font-weight: 600; color: var(--text-primary); }

        .filter-section {
            background: var(--card-surface);
            padding: 14px 18px;
            border-radius: 8px;
            border: 1px solid var(--border-subtle);
            margin-bottom: 16px;
            display: flex;
            gap: 12px;
            align-items: center;
        }
        .filter-section strong { font-size: 11px; color: var(--text-muted); text-transform: uppercase; }
        .filter-section input, .filter-section select {
            padding: 7px 12px;
            border: 1px solid var(--border-subtle);
            border-radius: 6px;
            font-size: 13px;
            color: var(--text-primary);
            background: var(--card-surface);
            outline: none;
        }
        .filter-section input { flex: 1; }

        .table-responsive {
            background: var(--card-surface);
            border-radius: 8px;
            border: 1px solid var(--border-subtle);
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; min-width: 650px; }
        th, td { padding: 12px 16px; border-bottom: 1px solid var(--border-subtle); color: var(--text-primary); }
        th { background: var(--table-th-bg); color: var(--text-secondary); font-weight: 600; font-size: 11px; text-transform: uppercase; }
        tr:hover td { background-color: var(--table-hover); }

        .badge { padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 600; text-transform: uppercase; display: inline-block; }
        .badge-normal { background: #d1fae5; color: #065f46; }
        .badge-down { background: #fee2e2; color: #991b1b; }
        [data-theme="dark"] .badge-normal { background: rgba(16, 185, 129, 0.25); color: #34d399; }
        [data-theme="dark"] .badge-down { background: rgba(239, 68, 68, 0.25); color: #f87171; }

        .action-btn { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; text-decoration: none; display: inline-block; margin-right: 3px; border: 1px solid transparent; }
        .btn-detail { background: #eff6ff; color: #1d4ed8; border-color: #dbeafe; }
        .btn-edit { background: #fef3c7; color: #b45309; border-color: #fde68a; }
        .btn-hapus { background: #fee2e2; color: #b91c1c; border-color: #fecaca; }
        [data-theme="dark"] .btn-detail { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }
        [data-theme="dark"] .btn-edit { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }
        [data-theme="dark"] .btn-hapus { background: rgba(239, 68, 68, 0.2); color: #f87171; }

        /* RESPONSIF HP */
        @media screen and (max-width: 768px) {
            body.sidebar-open-mobile .sidebar { transform: translateX(0); }
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0 !important; max-width: 100% !important; padding: 12px 16px !important; }
            .summary-cards { grid-template-columns: repeat(2, 1fr) !important; gap: 10px !important; }
            .container-grid { grid-template-columns: 1fr !important; }
            #map { height: 280px !important; }
            .chart-box { height: 300px !important; }
            .filter-section { flex-direction: column; align-items: stretch; }
            .filter-section input, .filter-section select { width: 100%; }
        }

        @media print {
            .sidebar, .top-navbar, .filter-section, .chart-box, #map, .action-btn, .theme-toggle-btn { display: none !important; }
            body { background: white !important; color: #000 !important; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .main-content { margin: 0 !important; padding: 0 !important; max-width: 100% !important; }
            .summary-cards { display: grid !important; grid-template-columns: repeat(4, 1fr) !important; gap: 10px !important; margin-bottom: 20px !important; }
            .card-stat { background: #ffffff !important; border: 1px solid #ccc !important; }
            .table-responsive { border: 1px solid #ccc !important; overflow: visible !important; }
            th { background: #f1f5f9 !important; color: #000 !important; }
        }
    </style>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="brand-logo-area" style="display: flex; align-items: center; gap: 10px;">
                <img src="logo_telkom.png" alt="Logo Telkom" style="width: 28px; height: auto; object-fit: contain;">
                <div>
                    <h2 style="margin: 0; font-size: 14px; font-weight: 500; color: #cbd5e1;">TELKOM <span style="color: var(--telkom-red); font-weight: 700;">NMS</span></h2>
                    <small style="display: block; font-size: 10px; color: #94a3b8; font-weight: 400;">Witel Jambi / Bungo</small>
                </div>
            </div>
            <button class="close-sidebar-btn" onclick="toggleSidebar()">✕</button>
        </div>
        <div class="sidebar-menu">
            <div class="menu-label">Navigasi Utama</div>
            <a href="index.php" class="sidebar-link active">📊 Dashboard & Topologi</a>
            <a href="redaman.php" class="sidebar-link">⚡ Test / Simulasi Redaman</a>
            
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                <a href="tambah.php" class="sidebar-link">➕ Tambah / Import Data</a>
            <?php endif; ?>
            
            <a href="export.php" class="sidebar-link">📥 Download Rekap Excel</a>
            <button onclick="window.print()" class="sidebar-link">🖨️ Cetak / Print Halaman</button>
            
            <div class="menu-label">Sistem</div>
            <a href="logout.php" class="sidebar-link" style="color: #f87171;">🚪 Keluar Sesi</a>
        </div>
        <div class="sidebar-footer">
            <div class="user-info" style="font-size: 12px; color: #94a3b8; font-weight: 400;">
                Pengguna Aktif:
                <b style="color: #cbd5e1; font-weight: 500; font-size: 13px; display: block; margin-top: 2px;"><?= $_SESSION['username']; ?> <span style="font-weight:normal; color:#94a3b8;">(<?= ucfirst($_SESSION['role']); ?>)</span></b>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="top-navbar">
            <div class="nav-left">
                <button class="burger-btn" onclick="toggleSidebar()">☰</button>
                <div class="page-title">
                    <h3>Monitoring Jaringan Akses & FTTH</h3>
                </div>
            </div>
            <div class="nav-right">
                <button class="theme-toggle-btn" onclick="toggleTheme()" id="themeToggleBtn">🌙 Mode Gelap</button>
                <div style="font-size: 12px; color: var(--text-secondary); font-weight: 600; display: none; @media(min-width:640px){display:block;}">
                    PT Telkom Indonesia
                </div>
            </div>
        </div>

        <div class="summary-cards">
            <div class="card-stat">
                <small>Total Infrastruktur</small>
                <h3><?= $total_node; ?> <span>Node</span></h3>
            </div>
            <div class="card-stat">
                <small>Total Titik Pasif</small>
                <h3><?= ($jml_odc + $jml_odp); ?> <span>ODC / ODP</span></h3>
            </div>
            <div class="card-stat">
                <small>Status Utama (OLT)</small>
                <h3><?= $jml_olt; ?> <span style="color: #10b981; font-size:12px;">Aktif</span></h3>
            </div>
            <div class="card-stat">
                <small>ODP Terdampak Gangguan</small>
                <h3 style="color: <?= ($odp_down > 0) ? '#ef4444' : '#10b981'; ?>;"><?= $odp_down; ?> <span>ODP</span></h3>
            </div>
        </div>

        <div class="container-grid">
            <div id="map"></div>
            <div class="chart-box">
                <h4>Komposisi Perangkat</h4>
                <div style="width: 100%; max-width: 180px;">
                    <canvas id="deviceChart"></canvas>
                </div>
            </div>
        </div>

        <div class="filter-section">
            <strong>Pencarian:</strong>
            <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Cari nama perangkat, ODP, ODC, atau IP...">
            <select id="filterJenis" onchange="filterTable()">
                <option value="">-- Semua Jenis --</option>
                <option value="Router">Router</option>
                <option value="Switch">Switch</option>
                <option value="Access Point">Access Point</option>
                <option value="OLT">OLT</option>
                <option value="ODC">ODC</option>
                <option value="ODP">ODP</option>
            </select>
        </div>

        <div class="table-responsive">
            <table id="dataTable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Perangkat / Node</th>
                        <th>Jenis</th>
                        <th>IP Address</th>
                        <th>Kapasitas Port</th>
                        <th>Status Jaringan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                $no = 1;
                $q_tabel = mysqli_query($koneksi, "SELECT * FROM perangkat");
                if ($q_tabel) {
                    while($row = mysqli_fetch_assoc($q_tabel)) : 
                        $status = getStatusPerangkat($row, $koneksi);
                ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><b><?= htmlspecialchars($row['nama_perangkat']); ?></b></td>
                    <td><?= htmlspecialchars($row['jenis']); ?></td>
                    <td><?= !empty($row['ip_address']) ? htmlspecialchars($row['ip_address']) : '<span style="color:var(--text-muted); font-style:italic;">- (Pasif)</span>'; ?></td>
                    <td><?= !empty($row['kapasitas_port']) ? htmlspecialchars($row['kapasitas_port']) : '-'; ?></td>
                    <td>
                        <?php if($status == 'Normal'): ?>
                            <span class="badge badge-normal">Normal</span>
                        <?php else: ?>
                            <span class="badge badge-down">Gangguan</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="detail.php?id=<?= $row['id']; ?>" class="action-btn btn-detail">Detail</a>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                            <a href="edit.php?id=<?= $row['id']; ?>" class="action-btn btn-edit">Edit</a>
                            <a href="hapus.php?id=<?= $row['id']; ?>" class="action-btn btn-hapus" onclick="return confirm('Yakin ingin menghapus perangkat ini?')">Hapus</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php 
                    endwhile; 
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const currentTheme = localStorage.getItem('theme') || 'light';
        if (currentTheme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
            document.getElementById('themeToggleBtn').innerHTML = '☀️ Mode Terang';
        }

        function toggleTheme() {
            let theme = document.documentElement.getAttribute('data-theme');
            if (theme === 'dark') {
                document.documentElement.setAttribute('data-theme', 'light');
                localStorage.setItem('theme', 'light');
                document.getElementById('themeToggleBtn').innerHTML = '🌙 Mode Gelap';
            } else {
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
                document.getElementById('themeToggleBtn').innerHTML = '☀️ Mode Terang';
            }
        }

        function toggleSidebar() {
            if (window.innerWidth <= 768) {
                document.body.classList.toggle('sidebar-open-mobile');
                document.getElementById('sidebarOverlay').classList.toggle('active');
            } else {
                document.body.classList.toggle('sidebar-collapsed');
            }
        }

        var map = L.map('map').setView([-1.4862, 102.1085], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

        <?php 
        $q_line = mysqli_query($koneksi, "SELECT p1.latitude as lat1, p1.longitude as lng1, p2.latitude as lat2, p2.longitude as lng2, p1.id as id1 
                                          FROM perangkat p1 JOIN perangkat p2 ON p1.parent_id = p2.id");
        if ($q_line) {
            while($l = mysqli_fetch_assoc($q_line)) :
                $q_cek = mysqli_query($koneksi, "SELECT * FROM perangkat WHERE id='{$l['id1']}'");
                $row_cek = mysqli_fetch_assoc($q_cek);
                $st_line = getStatusPerangkat($row_cek, $koneksi);
                $warna_line = ($st_line == 'Normal') ? '#3b82f6' : '#ef4444';
        ?>
            L.polyline([[<?= $l['lat1']; ?>, <?= $l['lng1']; ?>], [<?= $l['lat2']; ?>, <?= $l['lng2']; ?>]], {
                color: '<?= $warna_line; ?>', 
                weight: 3.5, 
                opacity: 0.9, 
                dashArray: '5, 5'
            }).addTo(map).bindPopup("<b>Status Jalur: <?= $st_line; ?></b>");
        <?php 
            endwhile; 
        }
        ?>

        <?php 
        $q_marker = mysqli_query($koneksi, "SELECT * FROM perangkat");
        if ($q_marker) {
            while($p = mysqli_fetch_assoc($q_marker)) : 
                $status_marker = getStatusPerangkat($p, $koneksi);
                
                if ($status_marker == 'Gangguan') {
                    $warna = '#ef4444'; // Otomatis Merah jika Down/Gangguan
                } else {
                    switch ($p['jenis']) {
                        case 'Router':
                            $warna = '#3b82f6'; // Biru Terang
                            break;
                        case 'Switch':
                            $warna = '#06b6d4'; // Cyan
                            break;
                        case 'Access Point':
                            $warna = '#ec4899'; // Pink
                            break;
                        case 'OLT':
                            $warna = '#8b5cf6'; // Ungu
                            break;
                        case 'ODC':
                            $warna = '#f59e0b'; // Kuning / Orange
                            break;
                        case 'ODP':
                            $warna = '#10b981'; // Hijau
                            break;
                        default:
                            $warna = '#6b7280'; // Abu-abu
                            break;
                    }
                }
                $radius_node = ($p['jenis'] == 'ODP') ? 6 : 9;
        ?>
            L.circleMarker([<?= $p['latitude']; ?>, <?= $p['longitude']; ?>], {
                radius: <?= $radius_node; ?>,
                fillColor: "<?= $warna; ?>",
                color: "#ffffff", 
                weight: 2, 
                fillOpacity: 1
            }).addTo(map).bindPopup(
                "<b><?= htmlspecialchars($p['nama_perangkat']); ?></b><br>" +
                "Jenis: <b><?= $p['jenis']; ?></b><br>" +
                "Status: <b style='color: <?= ($status_marker == 'Normal') ? '#10b981' : '#ef4444'; ?>'><?= $status_marker; ?></b><br>" +
                "<hr style='margin:5px 0; border:0; border-top:1px solid #e5e7eb;'>" +
                "<a href='detail.php?id=<?= $p['id']; ?>' target='_blank' style='color:#3b82f6; font-weight:600; text-decoration:none;'>🔍 Lihat Detail</a>"
            );
        <?php 
            endwhile; 
        }
        ?>

        const ctx = document.getElementById('deviceChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Router', 'OLT', 'ODC', 'ODP'],
                datasets: [{
                    data: [<?= $jml_router; ?>, <?= $jml_olt; ?>, <?= $jml_odc; ?>, <?= $jml_odp; ?>],
                    backgroundColor: ['#3b82f6', '#8b5cf6', '#f59e0b', '#10b981'],
                    borderWidth: 0
                }]
            },
            options: { 
                responsive: true, 
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { family: 'Plus Jakarta Sans', size: 11 } } } },
                cutout: '75%'
            }
        });

        function filterTable() {
            var input = document.getElementById("searchInput").value.toUpperCase();
            var jenis = document.getElementById("filterJenis").value.toUpperCase();
            var table = document.getElementById("dataTable");
            var tr = table.getElementsByTagName("tr");

            for (var i = 1; i < tr.length; i++) {
                var tdNama = tr[i].getElementsByTagName("td")[1];
                var tdJenis = tr[i].getElementsByTagName("td")[2];
                var tdIP = tr[i].getElementsByTagName("td")[3];
                
                if (tdNama || tdJenis || tdIP) {
                    var txtNama = tdNama.textContent || tdNama.innerText;
                    var txtJenis = tdJenis.textContent || tdJenis.innerText;
                    var txtIP = tdIP.textContent || tdIP.innerText;

                    var matchSearch = (txtNama.toUpperCase().indexOf(input) > -1 || txtIP.toUpperCase().indexOf(input) > -1);
                    var matchJenis = (jenis === "" || txtJenis.toUpperCase() === jenis);

                    if (matchSearch && matchJenis) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }
    </script>
</body>
</html>