INSERT INTO `perangkat` (`id`, `nama_perangkat`, `ip_address`, `jenis`, `parent_id`, `latitude`, `longitude`) VALUES
(1, 'Router Core Telkom Bungo', '8.8.8.8', 'Router', NULL, '-1.486200', '102.108500'),
(2, 'OLT Sentral Bungo', '192.168.1.1', 'OLT', 1, '-1.489000', '102.112000'),
(3, 'Switch Access Pasar Muara Bungo', '192.168.2.10', 'Switch', 2, '-1.482000', '102.102000'),
(4, 'OLT Unit Rimbo Bujang', '192.168.3.1', 'OLT', 1, '-1.378000', '102.065000');