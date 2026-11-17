-- Buat tabel users untuk Login Admin/User
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'user') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Masukkan data dummy akun (Password standar: 123456)
INSERT INTO `users` (`username`, `password`, `role`) VALUES
('admin_telkom', MD5('123456'), 'admin'),
('teknisi_lapangan', MD5('123456'), 'user');