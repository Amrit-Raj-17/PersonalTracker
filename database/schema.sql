DROP TABLE IF EXISTS tasks;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tasks (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(50) DEFAULT 'General',
    priority VARCHAR(20) DEFAULT 'Medium',
    status VARCHAR(30) DEFAULT 'Not Started',
    progress INT DEFAULT 0 CHECK (progress >= 0 AND progress <= 100),
    due_date DATE,
    completed BOOLEAN DEFAULT FALSE,
    completed_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_user
        FOREIGN KEY(user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);

-- Optional starter admin account
-- Password below is only an example hash for: admin123
/*
INSERT INTO users (name, email, password, role)
VALUES (
    'Admin',
    'admin@example.com',
    '$2y$10$u0S1nY2i4slU3n6xS5uN2u9eTq0UqL5.2XvG4m4t8c2M0rJ0h8f6K',
    'admin'
);

-- Example tasks
INSERT INTO tasks (
    user_id,
    title,
    description,
    category,
    priority,
    status,
    progress,
    completed
)
VALUES
(
    1,
    'Setup Work Tracker',
    'Create database and login system',
    'Project',
    'High',
    'In Progress',
    60,
    FALSE
),
(
    1,
    'Finish Dashboard Design',
    'Complete cards and task list UI',
    'Project',
    'Medium',
    'Completed',
    100,
    TRUE
);