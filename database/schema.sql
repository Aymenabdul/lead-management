-- Users Table for Authentication
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255),
    role VARCHAR(50) DEFAULT 'user', -- admin, user
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Leads Table
CREATE TABLE IF NOT EXISTS leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL, -- Associate who created this lead
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    email VARCHAR(255),
    platform VARCHAR(100),
    status VARCHAR(50) DEFAULT 'New', -- New, Contacted, Interested, Not Interested
    service VARCHAR(100), -- Web Development, App Development, Testing Service, Digital Marketing
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Converted Leads Table
CREATE TABLE IF NOT EXISTS converted_leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_lead_id INT,
    user_id INT NOT NULL, -- Associate who converted this lead
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    email VARCHAR(255),
    platform VARCHAR(100),
    service VARCHAR(100),
    payment_status VARCHAR(50) DEFAULT 'Pending', -- Pending, Paid, Refunded
    converted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(original_lead_id) REFERENCES leads(id) ON DELETE SET NULL,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

