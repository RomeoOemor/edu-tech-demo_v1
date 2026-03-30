CREATE TABLE IF NOT EXISTS students (
    id VARCHAR(40) PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(120) NOT NULL,
    grade VARCHAR(20) NOT NULL,
    bio TEXT NULL,
    interests TEXT NULL,
    friends TEXT NULL,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sick_leaves (
    id VARCHAR(40) PRIMARY KEY,
    student_id VARCHAR(40) NOT NULL,
    reason TEXT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    doctor_note VARCHAR(255) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    INDEX idx_student_id (student_id),
    CONSTRAINT fk_sick_leaves_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
