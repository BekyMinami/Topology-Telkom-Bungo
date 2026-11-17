-- Pastikan ID perangkat Anda disesuaikan jika berbeda, contoh menghubungkan rantai topologi:
UPDATE perangkat SET parent_id = (SELECT id FROM (SELECT id FROM perangkat WHERE jenis='Router' LIMIT 1) as t) WHERE jenis='Switch';
UPDATE perangkat SET parent_id = (SELECT id FROM (SELECT id FROM perangkat WHERE jenis='Switch' LIMIT 1) as t) WHERE jenis='OLT';
UPDATE perangkat SET parent_id = (SELECT id FROM (SELECT id FROM perangkat WHERE jenis='OLT' LIMIT 1) as t) WHERE jenis='ODC';
UPDATE perangkat SET parent_id = (SELECT id FROM (SELECT id FROM perangkat WHERE jenis='ODC' LIMIT 1) as t) WHERE jenis='ODP';