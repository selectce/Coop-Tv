-- ============================================================
--  SignageTV — Schema do Banco de Dados
--  Execute este arquivo uma vez no phpMyAdmin ou MySQL CLI
-- ============================================================

CREATE DATABASE IF NOT EXISTS signage_tv CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE signage_tv;

-- Usuários admin
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Lojas
CREATE TABLE IF NOT EXISTS stores (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    type ENUM('own','franchise') DEFAULT 'own',
    orientation ENUM('landscape','portrait') DEFAULT 'landscape',
    playback_order ENUM('sequential','random') DEFAULT 'sequential',
    single_video_mode TINYINT(1) DEFAULT 0,
    single_video_id INT DEFAULT NULL,
    show_controls TINYINT(1) DEFAULT 1,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Biblioteca de mídia
CREATE TABLE IF NOT EXISTS media (
    id INT PRIMARY KEY AUTO_INCREMENT,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    type ENUM('video','image') NOT NULL,
    mime_type VARCHAR(100),
    size BIGINT DEFAULT 0,
    duration DECIMAL(10,2) DEFAULT 0,   -- segundos (vídeo: detectado; imagem: padrão)
    width INT DEFAULT 0,
    height INT DEFAULT 0,
    thumb VARCHAR(255) DEFAULT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Itens da timeline por loja
CREATE TABLE IF NOT EXISTS timeline_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    store_id INT NOT NULL,
    media_id INT NOT NULL,
    position INT NOT NULL DEFAULT 0,
    duration DECIMAL(10,2) DEFAULT NULL,  -- NULL = usar duração original do vídeo
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    FOREIGN KEY (media_id) REFERENCES media(id) ON DELETE CASCADE
);

-- Log de reproduções
CREATE TABLE IF NOT EXISTS playback_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    store_id INT NOT NULL,
    media_id INT NOT NULL,
    played_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    FOREIGN KEY (media_id) REFERENCES media(id) ON DELETE CASCADE
);

-- Índices para performance de relatórios
CREATE INDEX idx_logs_store ON playback_logs(store_id);
CREATE INDEX idx_logs_media ON playback_logs(media_id);
CREATE INDEX idx_logs_date ON playback_logs(played_at);
CREATE INDEX idx_timeline_store ON timeline_items(store_id, position);

-- Admin padrão: usuário = admin / senha = admin123 (TROQUE APÓS INSTALAR!)
INSERT INTO users (username, password) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- 10 lojas de exemplo (edite os nomes depois)
INSERT INTO stores (name, slug, type) VALUES
('Loja 01 - Centro',       'loja-01', 'own'),
('Loja 02 - Norte',        'loja-02', 'own'),
('Loja 03 - Sul',          'loja-03', 'own'),
('Loja 04 - Leste',        'loja-04', 'own'),
('Loja 05 - Oeste',        'loja-05', 'own'),
('Franquia 01',            'franquia-01', 'franchise'),
('Franquia 02',            'franquia-02', 'franchise'),
('Franquia 03',            'franquia-03', 'franchise'),
('Franquia 04',            'franquia-04', 'franchise'),
('Franquia 05',            'franquia-05', 'franchise');
