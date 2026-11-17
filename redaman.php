<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$hasil_redaman = "";
$keterangan_status = "";
$warna_status = "";

// Logika Kalkulator Redaman
if (isset($_POST['hitung_redaman'])) {
    $panjang_km     = floatval($_POST['panjang_km']); // dalam kilometer
    $panjang_m      = $panjang_km * 1000;
    $jml_sambungan  = intval($_POST['jml_sambungan']);
    $jml_konektor   = intval($_POST['jml_konektor']);
    $panjang_gelombang = floatval($_POST['panjang_gelombang']); // 1310nm atau 1550nm

    // Standar Atenuasi Fiber Optik (ITU-T G.652)
    // 1310 nm: ~0.35 dB/km, 1550 nm: ~0.22 dB/km
    $atenuasi_kabel = ($panjang_gelombang == 1310) ? 0.35 : 0.22;
    
    $total_redaman_kabel = $panjang_km * $atenuasi_kabel;
    $total_redaman_sambungan = $jml_sambungan * 0.1; // Standar 0.1 dB per splice
    $total_redaman_konektor  = $jml_konektor * 0.5;  // Standar 0.5 dB per konektor pair

    // Total Redaman Keseluruhan (dB)
    $total_db = $total_redaman_kabel + $total_redaman_sambungan + $total_redaman_konektor;
    $total_db = round($total_db, 2);

    if ($total_db <= 15) {
        $keterangan_status = "Sangat Baik (Normal)";
        $warna_status = "#10b981"; // Hijau
    } elseif ($total_db <= 25) {
        $keterangan_status = "Standar / Layak (Normal FTTH)";
        $warna_status = "#3b82f6"; // Biru
    } else {
        $keterangan_status = "Buruk / Loss Tinggi (Perlu Pengecekan / Bending / Kotor)";
        $warna_status = "#ef4444"; // Merah
    }

    $hasil_redaman = [
        'total_db' => $total_db,
        'panjang_m' => $panjang_m,
        'kabel_db' => round($total_redaman_kabel, 2),
        'sambungan_db' => round($total_redaman_sambungan, 2),
        'konektor_db' => round($total_redaman_konektor, 2)
    ];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Redaman Fiber Optik - Telkom Bungo</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --telkom-red: #cc0000;
            --bg-canvas: #f3f4f6;
            --card-surface: #ffffff;
            --text-primary: #1f2937;
            --text-secondary: #4b5563;
            --border-subtle: #e5e7eb;
            --input-bg: #ffffff;
        }

        [data-theme="dark"] {
            --telkom-red: #ef4444;
            --bg-canvas: #0b0f19;
            --card-surface: #131d31;
            --text-primary: #f3f4f6;
            --text-secondary: #9ca3af;
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

        .wrapper { max-width: 650px; margin: 0 auto; }

        .header-nav-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

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
            transition: all 0.2s ease;
        }
        .btn-kembali:hover { color: var(--telkom-red); border-color: var(--telkom-red); }

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
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
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
            outline: none;
            transition: border-color 0.15s ease, background-color 0.2s ease;
        }
        input:focus, select:focus { border-color: var(--telkom-red); box-shadow: 0 0 0 3px rgba(204, 0, 0, 0.1); }

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
            transition: background 0.15s ease;
        }
        .btn-submit:hover { background: #b30000; }

        .result-box {
            background: rgba(59, 130, 246, 0.08);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 8px;
            padding: 16px;
            margin-top: 20px;
        }
        [data-theme="dark"] .result-box {
            background: rgba(59, 130, 246, 0.15);
        }
        .result-box h4 { margin: 0 0 10px 0; font-size: 15px; color: var(--text-primary); }
        .result-item { font-size: 13px; margin-bottom: 6px; color: var(--text-secondary); }
    </style>
</head>
<body>

    <div class="wrapper">
        <div class="header-nav-flex">
            <a href="index.php" class="btn-kembali">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                Kembali ke Peta Utama
            </a>
            <button class="theme-toggle-btn" onclick="toggleTheme()" id="themeToggleBtn">🌙 Mode Gelap</button>
        </div>

        <div class="card">
            <h3>Kalkulator Simulasi Redaman Fiber Optik (Link Budget)</h3>
            <form action="" method="POST">
                <label>Panjang Kabel (dalam Kilometer):</label>
                <input type="number" step="0.01" name="panjang_km" required placeholder="Contoh: 2.5 (artinya 2.5 km / 2500 meter)" value="<?= $_POST['panjang_km'] ?? ''; ?>">

                <label>Panjang Gelombang (Wavelength):</label>
                <select name="panjang_gelombang">
                    <option value="1310" <?= (isset($_POST['panjang_gelombang']) && $_POST['panjang_gelombang'] == 1310) ? 'selected' : ''; ?>>1310 nm (Standar Akses)</option>
                    <option value="1550" <?= (isset($_POST['panjang_gelombang']) && $_POST['panjang_gelombang'] == 1550) ? 'selected' : ''; ?>>1550 nm (Standar Distibusi/Long Haul)</option>
                </select>

                <label>Jumlah Titik Sambungan (Splice / Splicing):</label>
                <input type="number" name="jml_sambungan" required placeholder="Contoh: 4" value="<?= $_POST['jml_sambungan'] ?? '2'; ?>">

                <label>Jumlah Konektor / Adaptor:</label>
                <input type="number" name="jml_konektor" required placeholder="Contoh: 2" value="<?= $_POST['jml_konektor'] ?? '2'; ?>">

                <button type="submit" name="hitung_redaman" class="btn-submit">Hitung Estimasi Redaman</button>
            </form>

            <?php if ($hasil_redaman !== ""): ?>
                <div class="result-box">
                    <h4>Hasil Analisis Simulasi Redaman:</h4>
                    <div class="result-item"><b>Panjang Kabel:</b> <?= $hasil_redaman['panjang_m']; ?> Meter (<?= $_POST['panjang_km']; ?> km)</div>
                    <div class="result-item">• Redaman murni kabel: <b><?= $hasil_redaman['kabel_db']; ?> dB</b></div>
                    <div class="result-item">• Redaman sambungan (<?= $_POST['jml_sambungan']; ?> titik): <b><?= $hasil_redaman['sambungan_db']; ?> dB</b></div>
                    <div class="result-item">• Redaman konektor (<?= $_POST['jml_konektor']; ?> buah): <b><?= $hasil_redaman['konektor_db']; ?> dB</b></div>
                    <hr style="border:0; border-top:1px solid var(--border-subtle); margin: 10px 0;">
                    <div style="font-size: 16px; font-weight: 700; color: var(--text-primary);">
                        Total Estimasi Redaman: <span style="color: var(--telkom-red);"><?= $hasil_redaman['total_db']; ?> dB</span>
                    </div>
                    <div style="margin-top: 8px; font-size: 13px; font-weight: 600; color: <?= $warna_status; ?>">
                        Status: <?= $keterangan_status; ?>
                    </div>
                </div>
            <?php endif; ?>
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