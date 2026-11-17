<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validasi ketat: Jika belum login atau rolenya bukan admin, tendang keluar!
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}
include 'koneksi.php';

$pesan = "";

// 1. Logika Tambah Satuan (Manual)
if (isset($_POST['tambah_manual'])) {
    $nama           = mysqli_real_escape_string($koneksi, $_POST['nama_perangkat']);
    $jenis          = $_POST['jenis'];
    
    $ip_raw         = trim($_POST['ip_address']);
    $ip_address     = !empty($ip_raw) ? "'" . mysqli_real_escape_string($koneksi, $ip_raw) . "'" : "NULL";
    
    $kap_raw        = trim($_POST['kapasitas_port']);
    $kapasitas_port = !empty($kap_raw) ? "'" . mysqli_real_escape_string($koneksi, $kap_raw) . "'" : "NULL";
    
    $parent_id      = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : "NULL";
    $latitude       = mysqli_real_escape_string($koneksi, $_POST['latitude']);
    $longitude      = mysqli_real_escape_string($koneksi, $_POST['longitude']);

    $query = "INSERT INTO perangkat (nama_perangkat, jenis, ip_address, kapasitas_port, parent_id, latitude, longitude) 
              VALUES ('$nama', '$jenis', $ip_address, $kapasitas_port, $parent_id, '$latitude', '$longitude')";
    
    if (mysqli_query($koneksi, $query)) {
        $pesan = "<div class='alert alert-success'>Perangkat berhasil ditambahkan!</div>";
    } else {
        $pesan = "<div class='alert alert-danger'>Gagal: " . mysqli_error($koneksi) . "</div>";
    }
}

// 2. Logika Import File CSV Massal
if (isset($_POST['import_csv'])) {
    if (isset($_FILES['file_csv']['name']) && $_FILES['file_csv']['error'] === 0) {
        $file_tmp = $_FILES['file_csv']['tmp_name'];
        $handle = fopen($file_tmp, "r");
        
        // Lewati baris pertama (header)
        fgetcsv($handle, 1000, ",");

        $sukses = 0;
        $gagal = 0;
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($data) < 7) continue;

            $nama           = mysqli_real_escape_string($koneksi, $data[0]);
            $jenis          = mysqli_real_escape_string($koneksi, $data[1]);
            
            $ip_raw         = trim($data[2]);
            $ip_address     = !empty($ip_raw) ? "'" . mysqli_real_escape_string($koneksi, $ip_raw) . "'" : "NULL";
            
            $kap_raw        = trim($data[3]);
            $kapasitas_port = !empty($kap_raw) ? "'" . mysqli_real_escape_string($koneksi, $kap_raw) . "'" : "NULL";
            
            $parent_id      = !empty($data[4]) ? intval($data[4]) : "NULL";
            $latitude       = mysqli_real_escape_string($koneksi, $data[5]);
            $longitude      = mysqli_real_escape_string($koneksi, $data[6]);

            $q_import = "INSERT INTO perangkat (nama_perangkat, jenis, ip_address, kapasitas_port, parent_id, latitude, longitude) 
                         VALUES ('$nama', '$jenis', $ip_address, $kapasitas_port, $parent_id, '$latitude', '$longitude')";
            
            if (mysqli_query($koneksi, $q_import)) {
                $sukses++;
            } else {
                $gagal++;
            }
        }
        fclose($handle);
        $pesan = "<div class='alert alert-success'>Berhasil mengimport $sukses data perangkat dari CSV! " . ($gagal > 0 ? "(Gagal: $gagal)" : "") . "</div>";
    } else {
        $pesan = "<div class='alert alert-danger'>Gagal mengupload file CSV.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Perangkat - Telkom Bungo</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
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
            --input-bg: #ffffff;
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
            --input-bg: #0f172a;
        }

        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: var(--bg-canvas); 
            color: var(--text-primary);
            padding: 30px 20px; 
            margin: 0; 
            -webkit-font-smoothing: antialiased;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .wrapper {
            max-width: 650px; 
            margin: 0 auto;
        }

        .header-nav-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        /* Tombol Kembali dengan Ikon Panah SVG Tebal */
        .btn-kembali { 
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            background: var(--card-surface);
            border: 1px solid var(--border-subtle);
            border-radius: 8px;
            color: var(--text-secondary); 
            text-decoration: none; 
            font-weight: 600; 
            font-size: 13px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
            transition: all 0.2s ease;
        }
        .btn-kembali .arrow-icon {
            transition: transform 0.2s ease;
        }
        .btn-kembali:hover { 
            color: var(--telkom-red); 
            border-color: var(--telkom-red);
            background: rgba(204, 0, 0, 0.04);
        }
        .btn-kembali:hover .arrow-icon {
            transform: translateX(-3px);
        }

        .theme-toggle-btn {
            background: var(--card-surface);
            border: 1px solid var(--border-subtle);
            padding: 8px 14px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
            font-family: 'Plus Jakarta Sans', sans-serif;
            transition: background 0.15s ease;
        }
        .theme-toggle-btn:hover { background: var(--border-subtle); }

        .card { 
            background: var(--card-surface); 
            padding: 28px 32px; 
            border-radius: 12px; 
            border: 1px solid var(--border-subtle);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
            margin-bottom: 24px; 
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }

        h3 { 
            color: var(--text-primary); 
            margin-top: 0; 
            font-size: 17px;
            font-weight: 700;
            border-left: 4px solid var(--telkom-red);
            padding-left: 10px;
            margin-bottom: 20px;
        }

        label { 
            display: block; 
            margin-top: 14px; 
            font-weight: 600; 
            font-size: 12px; 
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        input, select { 
            width: 100%; 
            padding: 10px 14px; 
            margin-top: 6px; 
            border: 1px solid var(--border-subtle); 
            border-radius: 8px; 
            box-sizing: border-box; 
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 13px;
            color: var(--text-primary);
            background: var(--input-bg);
            transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.2s ease;
            outline: none;
        }

        input:focus, select:focus {
            border-color: var(--telkom-red);
            box-shadow: 0 0 0 3px rgba(204, 0, 0, 0.1);
        }

        input[type="file"] {
            padding: 8px;
            background: var(--bg-canvas);
            cursor: pointer;
        }

        .btn-submit { 
            background: var(--telkom-red); 
            color: white; 
            padding: 12px 18px; 
            border: none; 
            border-radius: 8px; 
            font-weight: 600; 
            font-size: 13px;
            cursor: pointer; 
            margin-top: 22px; 
            width: 100%; 
            font-family: 'Plus Jakarta Sans', sans-serif;
            transition: background 0.15s ease, transform 0.1s ease;
            box-shadow: 0 2px 4px rgba(204, 0, 0, 0.2);
        }
        .btn-submit:hover { background: #b30000; }
        .btn-submit:active { transform: scale(0.99); }

        .btn-import {
            background: #2563eb;
            box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
        }
        .btn-import:hover { background: #1d4ed8; }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 16px;
            font-weight: 500;
        }
        .alert-success { background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
        .alert-danger { background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }

        p.desc {
            font-size: 13px; 
            color: var(--text-secondary); 
            line-height: 1.5;
            margin-top: 0;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>

    <div class="wrapper">
        <div class="header-nav-flex">
            <!-- Tombol Kembali dengan Ikon Panah SVG Tebal -->
            <a href="index.php" class="btn-kembali">
                <svg class="arrow-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Kembali ke Peta Utama
            </a>
            <button class="theme-toggle-btn" onclick="toggleTheme()" id="themeToggleBtn">🌙 Mode Gelap</button>
        </div>

        <!-- FORM 1: TAMBAH SATUAN MANUAL -->
        <div class="card">
            <h3>Tambah Perangkat / Node Manual</h3>
            <?= $pesan; ?>
            <form action="" method="POST">
                <label>Nama Perangkat / ODP / ODC:</label>
                <input type="text" name="nama_perangkat" required placeholder="Contoh: ODP-BNG-FA/01">

                <label>Jenis Perangkat:</label>
                <select name="jenis" required>
                    <option value="Router">Router</option>
                    <option value="Switch">Switch</option>
                    <option value="Access Point">Access Point</option>
                    <option value="OLT">OLT</option>
                    <option value="ODC">ODC</option>
                    <option value="ODP">ODP</option>
                </select>

                <label>IP Address (Kosongkan jika ODC/ODP pasif):</label>
                <input type="text" name="ip_address" placeholder="Contoh: 192.168.1.1 (Opsional)">

                <label>Kapasitas Port / Splitter:</label>
                <input type="text" name="kapasitas_port" placeholder="Contoh: 8 Port (Splitter 1:8)">

                <label>Terhubung Ke (Parent Induk):</label>
                <select name="parent_id">
                    <option value="">-- Pilih Induk (Parent) --</option>
                    <?php 
                    $res = mysqli_query($koneksi, "SELECT * FROM perangkat");
                    while($p = mysqli_fetch_assoc($res)){
                        echo "<option value='{$p['id']}'>{$p['nama_perangkat']} ({$p['jenis']})</option>";
                    }
                    ?>
                </select>

                <label>Latitude (Garis Lintang):</label>
                <input type="text" name="latitude" required placeholder="Contoh: -1.486200">

                <label>Longitude (Garis Bujur):</label>
                <input type="text" name="longitude" required placeholder="Contoh: 102.108500">

                <button type="submit" name="tambah_manual" class="btn-submit">Simpan Perangkat</button>
            </form>
        </div>

        <!-- FORM 2: UPLOAD / IMPORT FILE CSV -->
        <div class="card">
            <h3>Import Data Massal (File CSV)</h3>
            <p class="desc">Jika Anda memiliki data dari Excel, simpan atau <i>export</i> terlebih dahulu ke format <b>CSV (Comma Delimited)</b> lalu upload di sini.</p>
            <form action="" method="POST" enctype="multipart/form-data">
                <label>Pilih File CSV:</label>
                <input type="file" name="file_csv" accept=".csv" required>
                <button type="submit" name="import_csv" class="btn-submit btn-import">Upload & Import Data</button>
            </form>
        </div>
    </div>

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
    </script>
</body>
</html>