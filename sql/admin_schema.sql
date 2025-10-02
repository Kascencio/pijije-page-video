-- Esquema adicional para panel de administración

-- Tabla de administradores
CREATE TABLE admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(120) NOT NULL UNIQUE,
  pass_hash CHAR(60) NOT NULL,
  role ENUM('super_admin', 'admin') DEFAULT 'admin',
  active TINYINT(1) DEFAULT 1,
  last_login DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX idx_username (username),
  INDEX idx_email (email),
  INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de configuración del sistema
CREATE TABLE system_config (
  id INT AUTO_INCREMENT PRIMARY KEY,
  config_key VARCHAR(100) NOT NULL UNIQUE,
  config_value TEXT,
  description TEXT,
  updated_by INT,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (updated_by) REFERENCES admins(id) ON DELETE SET NULL,
  INDEX idx_config_key (config_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de logs de administración
CREATE TABLE admin_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  admin_id INT,
  action VARCHAR(100) NOT NULL,
  target_type VARCHAR(50), -- 'user', 'video', 'order', 'config'
  target_id INT,
  details JSON,
  ip_address VARCHAR(45),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL,
  INDEX idx_admin_id (admin_id),
  INDEX idx_action (action),
  INDEX idx_target (target_type, target_id),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar admin por defecto (password: admin123)
INSERT INTO admins (username, email, pass_hash, role) VALUES 
('admin', 'admin@organicos.com', '$argon2id$v=19$m=65536,t=4,p=3$dGVzdA$test', 'super_admin');

-- Configuración inicial del sistema
INSERT INTO system_config (config_key, config_value, description) VALUES
('course_price', '1500', 'Precio del curso en centavos'),
('course_title', 'Curso de Ganadería Regenerativa', 'Título del curso'),
('course_description', 'Aprende ganadería regenerativa de expertos', 'Descripción del curso'),
('course_duration', '3', 'Duración del acceso en meses'),
('paypal_client_id', 'BAAipD-neAwq8ipyuWBvR2fuwvHBZXSH01lloe6EczcKmt4VSmr_FdUCZ-2sWm7Hn1hGs_s0OZmXE7PTVI', 'Client ID de PayPal'),
-- ('paypal_hosted_button_id', 'DBG5YUH74U5A6', 'ID del botón hosted de PayPal'), -- Removido: Smart Buttons
('contact_email', 'organicosdeltropico@yahoo.com.mx', 'Email de contacto'),
('contact_phone', '+52 93 4115 0595', 'Teléfono de contacto');
