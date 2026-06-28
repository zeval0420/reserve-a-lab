-- School Finance Management System
-- Database: db_admin
-- Only adds new tables, preserving existing data

USE db_admin;

-- Events / Projects
CREATE TABLE IF NOT EXISTS sf_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('Planned', 'Active', 'Completed') DEFAULT 'Planned',
    color VARCHAR(7) DEFAULT '#3B82F6',
    icon VARCHAR(50) DEFAULT 'calendar',
    is_archived TINYINT(1) DEFAULT 0,
    archived_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Income Categories (nested under events)
CREATE TABLE IF NOT EXISTS sf_income_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES sf_events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Income Records
CREATE TABLE IF NOT EXISTS sf_income (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    category_id INT NULL,
    description VARCHAR(500) NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    transaction_date DATE NOT NULL,
    notes TEXT,
    created_by VARCHAR(100) DEFAULT 'system',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (event_id) REFERENCES sf_events(id),
    FOREIGN KEY (category_id) REFERENCES sf_income_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Expense Records
CREATE TABLE IF NOT EXISTS sf_expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    description VARCHAR(500) NOT NULL,
    quantity DECIMAL(10,3) DEFAULT 1,
    unit_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    amount DECIMAL(12,2) GENERATED ALWAYS AS (quantity * unit_price) STORED,
    transaction_date DATE NOT NULL,
    notes TEXT,
    is_reimbursed TINYINT(1) DEFAULT 0,
    reimbursed_date DATE NULL,
    reimbursed_notes TEXT NULL,
    created_by VARCHAR(100) DEFAULT 'system',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (event_id) REFERENCES sf_events(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Receipt Attachments
CREATE TABLE IF NOT EXISTS sf_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expense_id INT NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(100),
    file_size INT DEFAULT 0,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (expense_id) REFERENCES sf_expenses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Edit History for Income
CREATE TABLE IF NOT EXISTS sf_income_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    income_id INT NOT NULL,
    changed_by VARCHAR(100),
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    field_name VARCHAR(100),
    old_value TEXT,
    new_value TEXT,
    FOREIGN KEY (income_id) REFERENCES sf_income(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Edit History for Expenses
CREATE TABLE IF NOT EXISTS sf_expense_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expense_id INT NOT NULL,
    changed_by VARCHAR(100),
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    field_name VARCHAR(100),
    old_value TEXT,
    new_value TEXT,
    FOREIGN KEY (expense_id) REFERENCES sf_expenses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System Users (simple auth)
CREATE TABLE IF NOT EXISTS sf_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('treasurer', 'auditor') NOT NULL DEFAULT 'treasurer',
    display_name VARCHAR(150),
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default password for seeded users: "password"
INSERT IGNORE INTO sf_users (username, password_hash, role, display_name) VALUES
('treasurer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'treasurer', 'School Treasurer'),
('auditor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'auditor', 'School Auditor');

INSERT IGNORE INTO sf_events (id, name, description, status, color) VALUES
(1, 'Teachers Day', 'Annual Teachers Day celebration', 'Completed', '#10B981'),
(2, 'Christmas Party', 'Year-end Christmas celebration', 'Active', '#F59E0B'),
(3, 'Membership Collection', 'Annual membership fee collection', 'Active', '#3B82F6'),
(4, 'Foundation Day', 'School foundation day activities', 'Planned', '#8B5CF6');

INSERT IGNORE INTO sf_income_categories (event_id, name, sort_order) VALUES
(1, 'Student Collection', 1),(1, 'Donations', 2),
(2, 'Student Payments', 1),(2, 'Solicitations', 2),(2, 'Donations', 3),
(3, 'Membership Collection', 1);

INSERT IGNORE INTO sf_income (event_id, category_id, description, amount, transaction_date) VALUES
(1, 1, 'Section A Student Collection', 2500.00, '2024-10-01'),
(1, 1, 'Section B Student Collection', 2300.00, '2024-10-02'),
(1, 2, 'Faculty Donations', 1500.00, '2024-10-03'),
(2, 3, 'Student Payments Batch 1', 5000.00, '2024-11-15'),
(2, 4, 'Business Solicitation - ABC Store', 3000.00, '2024-11-20'),
(2, 5, 'Alumni Donations', 2000.00, '2024-11-25'),
(3, 6, 'First Batch Membership Fees', 8500.00, '2024-09-05');

INSERT IGNORE INTO sf_expenses (event_id, description, quantity, unit_price, transaction_date) VALUES
(1, 'Tarpaulin Printing', 2, 350.00, '2024-10-04'),
(1, 'Cake and Refreshments', 1, 1800.00, '2024-10-05'),
(1, 'Flower Arrangements', 5, 150.00, '2024-10-05'),
(2, 'Venue Decoration Materials', 1, 2500.00, '2024-11-28'),
(2, 'Food and Catering', 50, 120.00, '2024-12-20'),
(2, 'Sound System Rental', 1, 1500.00, '2024-12-20'),
(3, 'Membership ID Cards Printing', 100, 15.00, '2024-09-06');
