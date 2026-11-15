CREATE DATABASE IF NOT EXISTS securevault CHARACTER SET utf8mb4;
USE securevault;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Semua konten disimpan dalam bentuk terenkripsi (tidak ada plaintext di database)
CREATE TABLE IF NOT EXISTS contents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title_enc TEXT NOT NULL,      -- AES-256-GCM (simetris), base64(iv+tag+ciphertext)
  body_enc  MEDIUMTEXT NOT NULL,-- AES-256-GCM (simetris)
  enc_key   TEXT NOT NULL,      -- kunci AES dibungkus RSA-2048 OAEP (asimetris)
  signature TEXT NOT NULL,      -- tanda tangan digital RSA-SHA256 (asimetris)
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
