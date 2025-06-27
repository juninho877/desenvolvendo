-- SQL para migração do sistema FutBanner para MySQL
-- Execute este script no seu banco de dados MySQL

-- 1. Criar banco de dados (se não existir)
CREATE DATABASE IF NOT EXISTS futbanner_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE futbanner_db;

-- 2. Criar tabela de usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE,
    role ENUM('admin', 'user') DEFAULT 'user',
    status ENUM('active', 'inactive') DEFAULT 'active',
    expires_at DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_role (role)
);

-- 3. Criar tabela de sessões de usuário
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_session_token (session_token),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
);

-- 4. Criar tabela de imagens personalizadas por usuário
CREATE TABLE IF NOT EXISTS user_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    image_key VARCHAR(50) NOT NULL COMMENT 'Chave da imagem: logo_banner_1, background_banner_2, card_banner_3, etc.',
    image_path VARCHAR(500) NOT NULL COMMENT 'Caminho do arquivo ou URL da imagem',
    upload_type ENUM('file', 'url', 'default') DEFAULT 'file' COMMENT 'Tipo: arquivo local, URL externa ou padrão',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Chave única para garantir que cada usuário tenha apenas uma configuração por tipo de imagem
    UNIQUE KEY unique_user_image (user_id, image_key),
    
    -- Chave estrangeira para usuários
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    
    -- Índices para performance
    INDEX idx_user_image_key (user_id, image_key),
    INDEX idx_image_key (image_key),
    INDEX idx_upload_type (upload_type)
);

-- 5. Inserir usuário administrador padrão (senha: admin123)
INSERT IGNORE INTO usuarios (username, password, email, role, status) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@futbanner.com', 'admin', 'active');

-- 6. Inserir imagens padrão para todos os usuários existentes
INSERT IGNORE INTO user_images (user_id, image_key, image_path, upload_type)
SELECT 
    u.id,
    'logo_banner_1',
    'imgelementos/semlogo.png',
    'default'
FROM usuarios u;

INSERT IGNORE INTO user_images (user_id, image_key, image_path, upload_type)
SELECT 
    u.id,
    'logo_banner_2',
    'imgelementos/semlogo.png',
    'default'
FROM usuarios u;

INSERT IGNORE INTO user_images (user_id, image_key, image_path, upload_type)
SELECT 
    u.id,
    'logo_banner_3',
    'imgelementos/semlogo.png',
    'default'
FROM usuarios u;

INSERT IGNORE INTO user_images (user_id, image_key, image_path, upload_type)
SELECT 
    u.id,
    'background_banner_1',
    'fzstore/Img/background_banner_1.png',
    'default'
FROM usuarios u;

INSERT IGNORE INTO user_images (user_id, image_key, image_path, upload_type)
SELECT 
    u.id,
    'background_banner_2',
    'fzstore/Img/background_banner_2.jpg',
    'default'
FROM usuarios u;

INSERT IGNORE INTO user_images (user_id, image_key, image_path, upload_type)
SELECT 
    u.id,
    'background_banner_3',
    'fzstore/Img/background_banner_3.png',
    'default'
FROM usuarios u;

INSERT IGNORE INTO user_images (user_id, image_key, image_path, upload_type)
SELECT 
    u.id,
    'card_banner_1',
    'fzstore/card/card_banner_1.png',
    'default'
FROM usuarios u;

INSERT IGNORE INTO user_images (user_id, image_key, image_path, upload_type)
SELECT 
    u.id,
    'card_banner_2',
    'fzstore/card/card_banner_2.png',
    'default'
FROM usuarios u;

INSERT IGNORE INTO user_images (user_id, image_key, image_path, upload_type)
SELECT 
    u.id,
    'card_banner_3',
    'fzstore/card/card_banner_3.png',
    'default'
FROM usuarios u;

-- 7. Verificar se as tabelas foram criadas corretamente
SELECT 'Tabelas criadas com sucesso!' as status;
SHOW TABLES;

-- 8. Verificar dados inseridos
SELECT 'Usuários cadastrados:' as info;
SELECT id, username, role, status, created_at FROM usuarios;

SELECT 'Configurações de imagem por usuário:' as info;
SELECT 
    u.username,
    ui.image_key,
    ui.upload_type,
    ui.created_at
FROM user_images ui
JOIN usuarios u ON ui.user_id = u.id
ORDER BY u.username, ui.image_key;