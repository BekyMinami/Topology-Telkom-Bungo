<?php
include 'koneksi.php';
$out = [];
$q = mysqli_query($koneksi, "SELECT * FROM perangkat");
while ($r = mysqli_fetch_assoc($q)) {
    $out[] = [
        'id'     => (int)$r['id'],
        'nama'   => $r['nama_perangkat'],
        'jenis'  => $r['jenis'],
        'ip'     => $r['ip_address'] ?? '',
        'port'   => $r['kapasitas_port'] ?? '',
        'parent' => $r['parent_id'] ? (int)$r['parent_id'] : null,
        'lat'    => (float)$r['latitude'],
        'lng'    => (float)$r['longitude'],
        'status' => !empty($r['ip_address']) ? pingIP($r['ip_address']) : ''
    ];
}
function pingIP($ip) {
    $cmd = (PHP_OS_FAMILY === 'Windows') ? "ping -n 1 -w 500 " : "ping -c 1 -W 1 ";
    exec($cmd . escapeshellarg($ip), $o, $s);
    return $s === 0 ? 'Normal' : 'Gangguan';
}
header('Content-Type: text/plain');
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);