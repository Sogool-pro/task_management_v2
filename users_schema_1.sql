CREATE TABLE IF NOT EXISTS users (
    id INT NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(50) NOT NULL,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    bio TEXT,
    phone VARCHAR(20) DEFAULT NULL,
    address TEXT,
    skills TEXT,
    profile_image VARCHAR(255) DEFAULT 'default.png',
    must_change_password TINYINT(1) DEFAULT 0,
    CONSTRAINT users_pkey PRIMARY KEY (id),
    CONSTRAINT users_role_check CHECK (role IN ('admin', 'employee'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

